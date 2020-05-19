<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Visita;
use App\User;
use App\Http\Requests\VisitaRequest;
use App\Notifications\SolicitacaoVisita;
use App\Notifications\SolicitacaoVisitaAceita;
use App\Notifications\SolicitacaoVisitaRecusada;
use App\Notifications\ConfirmarEmailVisita;
use \Illuminate\Notifications\Notifiable;
use Notification;
use Auth;

use Illuminate\Support\Facades\DB;

class VisitaController extends Controller
{

    protected $visita;
    protected $usuario;
    protected $auth;

    public function __construct(Visita $visita, User $usuario, Auth $auth) 
    {
        $this->visita = $visita;
        $this->usuario = $usuario;
        $this->auth = $auth;

        $this->middleware('auth', ['except' => [
            'busca',
            'salvar',
            'salvarUsuarioVisita'
        ]]);

    }

    public function index()
    {
        $registros = $this->visita->whereHas('user', function($query) {
            $query->whereNotNull('email_verified_at');
            })->get()->reverse();
        return view('auth.visitas.index', compact('registros'));
    }

    public function busca() 
    {
        return view('site.visitas.pesquisar_email');        
    }

    public function salvar($visita) 
    {
         $visita=$this->visita->create($visita);
         $visita['slug']='visita'.'-'.$visita->id;
         $visita->update($visita->attributesToArray());
    }

    public function ver(Visita $registro)
    {
        return view('auth.visitas.ver', compact('registro'));
    }

    public function atualizar(Request $request, $identifier)
    {
        $dados = $request->all();
        
        if($dados['confirmada'] == true) {
            $dados['confirmada'] = 1;
        } else {
            $dados['confirmada'] = 0;
        }

        $this->visita->find($identifier)->update($dados);

        $visita = $this->visita->where('id', $identifier)->first();
        $visita->user->notify(new SolicitacaoVisitaAceita($visita->user));

        return redirect()->route('auth.visitas')->with('success', 'Visita confirmada com sucesso, o responsável será avisado por email');
    }

    public function deletar($identifier) 
    {
        $visita = $this->visita->where('id', $identifier)->first();
        
        if(!$this->visita->find($identifier)->delete()) {
            return redirect()->back()->withErrors('error', 'Erro ao cancelar a visita');
        }

        $visita->user->notify(new SolicitacaoVisitaRecusada($visita->user));

        return redirect()->route('auth.visitas')->with('success', 'Visita cancelada com sucesso, o responsável será avisado por email');;
    }

    public function salvarUsuarioVisita(VisitaRequest $request)
    {
        $msgSucesso = 'Visita solicitada com sucesso, você receberá um email quando ela for confirmada.';

        $request->validated();

        $email = $request['email'];
        $userExiste = $this->usuario->where('email', $email)->first();

        if($userExiste == null) {
            
            $usuario = [
                'name' =>  $request['name'],
                'cpf' =>  $request['cpf'],
                'email' =>  $request['email'],
                'telephone' =>  $request['telephone'],
                'surname' =>  $request['surname'],
                'user_type' => 'visitant',
            ];

            $request->validate([
                'cpf' => 'unique:users',
            ],[
                'cpf.unique' => 'O CPF já foi cadastrado, tenta usar o mesmo email vinculado ao cpf'
            ]);

            $userExiste = $this->usuario->salvarUserVisitante($usuario);
            
            Notification::send($userExiste, new ConfirmarEmailVisita($userExiste));
            $msgSucesso = 'Visita solicitada com sucesso, <strong>você deve verificar seu email para concluir a solicitação.<strong>';
        }

        $visita = [
            'data' => $request['data'],
            'hora_inicial' => $request['hora_inicial'], 
            'hora_final' => $request['hora_final'], 
            'descricao' => $request['descricao'], 
            'confirmada' => (Auth::user() ? ($request['confirmada'] ? 1 : 0) : 0),
            'user_id' => $userExiste->id,
        ];

        $this->salvar($visita);

        return redirect()->route('site.visita.busca')->with('success', $msgSucesso);
    }
}
