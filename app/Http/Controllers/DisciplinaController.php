<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Disciplina;
use App\User;
use Validator;
use App\Http\Requests\CriarDisciplinaRequest;
use App\Http\Requests\AtualizarDisciplinaRequest;

class DisciplinaController extends Controller
{

    // Model de disciplina adicionado ao controller para evitar uso estatico
    protected $disciplina;
    protected $user;

    public function __construct(Disciplina $disciplina, User $user)
    {
        $this->middleware('auth');
        $this->disciplina = $disciplina;
        $this->user = $user;
    }

    public function index() 
    {
        $registros = $this->disciplina->all();
        return view('auth.disciplinas.index', compact('registros'));
    }

    public function adicionar() 
    {
        $users = $this->user
            ->where('user_type', 'admin')
            ->whereNotNull('cpf_verified_at')
            ->where('user_type', 'admin')
            ->get();
        return view('auth.disciplinas.adicionar', compact('users'));

    }

    public function salvar(CriarDisciplinaRequest $request) 
    {
        $request['nome'] = strtolower($request['nome']);

        $request->validated();

        $dados = $request->all();
        $disciplina=$this->disciplina->create($dados);
        $disciplina['slug']=str_slug( $disciplina->nome).'-'. $disciplina->id;
        $disciplina->update($disciplina->attributesToArray());
        return redirect()->route('auth.disciplinas')->with('success', 'Disciplina adicionada com sucesso!');
    }

    public function editar(Disciplina $registro) 
    {
        $users = $this->user->all();
        return view('auth.disciplinas.editar', compact('registro', 'users'));        

    }

    public function atualizar(AtualizarDisciplinaRequest $request, $identifier)
    {
        $request['nome'] = strtolower($request['nome']);
    
        $request->validated();

        $dados = $request->all();
        $dados['slug']=str_slug($dados['nome']).'-'.$identifier;
        if($this->disciplina->find($identifier)->nome != $request['nome']) {
            return redirect()->back()->withErrors(['nome' => 'O nome da disciplina não pode ser alterado']);
        }

        $this->disciplina->find($identifier)->update($dados);

        return redirect()->route('auth.disciplinas')->with('success', 'Disciplina atualizada com sucesso!');
    }

    public function deletar(Disciplina $registro)
    {
        $registro->delete();
        return redirect()->route('auth.disciplinas')->with('success', 'Disciplina deletada com sucesso!');
    }
}
