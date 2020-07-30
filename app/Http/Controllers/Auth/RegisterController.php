<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Foundation\Auth\RegistersUsers;
use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Auth;
use Illuminate\Http\Request;
use Illuminate\Foundation\Http\FormRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\EmailVisitaRequest;
use App\Notifications\SolicitacaoAcesso;
use App\Notifications\SolicitacaoAcesso_aceita;
use App\Notifications\SolicitacaoAcesso_recusada;
use \Illuminate\Notifications\Notifiable;
use Notification;
use App\Conta;
use App\Visita;
use App\Contato;
use App\User;
use Illuminate\Http\File;
use Illuminate\Support\Facades\Storage;
use Image;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Illuminate\Validation\Rule;
class RegisterController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Register Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users as well as their
    | validation and creation. By default this controller uses a trait to
    | provide this functionality without requiring any additional code.
    |
    */

    use RegistersUsers;

    /**
     * Where to redirect users after registration.
     *
     * @var string
     */
    protected $redirectTo = RouteServiceProvider::HOME;
    protected $usuario;
    protected $visita;
    protected $conta;
    protected $contato;
  
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    
     public function __construct(User $usuario, Visita $visita,Conta $conta, Contato $contato) 
    {
       $this->middleware('auth', ['except' => [

            'register','showRegistrationForm','siteIndex','siteRegistervizualizar', 'buscarUsuarioVisita','vizualizarTermosPrivacidade']]);
       $this->middleware('guest', ['only' => [
            'register',
            'showRegistrationForm'
        ]]);
      
       $this->usuario = $usuario;
       $this->visita = $visita;
       $this->conta = $conta;
       $this->contato = $contato;
       
    }

    protected function validator(array $data)
    {
        return Validator::make($data, [

            'name' =>'required|regex:/^[\pL\s\-.]+$/u|string|min:3|max:255',
            'email' => ['required', 'string', 'email', 'max:255',Rule::unique('users')->where(function ($query){return $query->where('user_type','admin');})],
            'password' => 'required|regex:/^(?=.*?[A-Z])(?=.*?[a-z])(?=.*?[0-9])(?=.*?[#?!@$%^&*-]).{6,}$/|string|min:6|confirmed',
	    'cpf' => ['required','regex:/\d{3}\.\d{3}\.\d{3}\-\d{2}/','string',Rule::unique('users')->where(function ($query){return $query->where('user_type','admin');})],

            'user_description' => 'max:255|nullable',
            'link_lattes' => 'url|string|nullable',
            'avatar' => 'mimes:jpeg,jpg,png,gif|max:2048|nullable',
	        'g-recaptcha-response' => 'required',
            
        ],[
       'password.regex'=>'Sua senha deve conter no mínimo de 6 caracteres,deve conter pelo menos uma letra maiúscula,uma minúscula,um número e um símbolo',
       'email.unique'=>'O valor informado para o campo e-mail já está em uso em uma conta de administrador',
       'cpf.unique'=>'O valor informado para o campo cpf já está em uso em uma conta de administrador',
       'g-recaptcha-response.required' => 'O campo reCaptcha é obrigatório',
       ]);
       
    }
    
    /**
     * Create a new user instance after a valid registration.
     *
     * @param  array  $data
     * @return \App\User
     */
     
    protected function create(array $data)
    {  $registros=$this->usuario->where( 'user_type', 'admin')->get()->whereNotNull('cpf_verified_at')->all();
       $findUser=$this->usuario->where('email',$data['email'])->first();
       $avatar=null;
       $request = new Request($data);
       
       $dados=[
            'name' =>  $data ['name'],
            'cpf' =>  $data ['cpf'],
            'email' =>  $data['email'],
            'user_description' =>$data ['user_description'],
            'avatar' => $avatar,
            'user_type' => 'admin',
            'link_lattes'=> $data['link_lattes'],
        ];

       if($findUser==null){//se não existe usuário cadastrado
       $user= $this->usuario->create($dados);
       }
       else if($findUser!=null){//se já existe usuário visitante cadastrado
          $findUser->update($dados);
          $user=$findUser;
       }
       
        $this->conta->create([
            'password' => Hash::make( $data ['password']),
            'user_id'=>$user->id,  
        ]);

        $user['slug']=str_slug($user->name).'-'.$user->id;
	if($request->has('avatar')) {
            $anexo = $data['avatar'];
            $dir = 'img/avatares/';
            $exAnexo =$anexo->guessClientExtension();
            $nomeAnexo = 'avatar_'.$user['slug'].'.'.$exAnexo;
            $anexo->move($dir, $nomeAnexo);
            $user['avatar']= $dir.'/'.$nomeAnexo;
           
        }
        $user->update($user->attributesToArray());

	foreach ($registros as $registro) {
            $registro->notify(new SolicitacaoAcesso($user));
        }
        return $user;
    }

    public function index (){
    	return view('auth.registros.index ');
    }

    public function gerenciarSolicitacao(){
        $usersAdmin=$this->usuario->where( 'user_type', 'admin')->latest();
        $registros= $usersAdmin->where('cpf_verified_at',null)->get();
        return view('auth.acesso_gerenciamento', compact('registros'));
    }

    public function aceitarSolicitacao($id_user){//lembrar de enviar notificação
        $user=$this->usuario->find($id_user);
        $user['cpf_verified_at']=now();
        $dados=[$user];
	    $user->update($dados);
        Notification::send($user,new SolicitacaoAcesso_aceita(Auth::user()));
        return redirect()->route('auth.acesso_gerenciamento')->with('success','Solicitação confirmada com sucesso'); 
    }

    public function recusarSolicitacao($id_user){
        $user=$this->usuario->find($id_user);
        $visitas=$this->visita->where('user_id',$user->id)->get();
        $conta=$this->conta->where('user_id',$user->id)->first();
        $dados=$user->getAttributes();
        Notification::send($user,new SolicitacaoAcesso_recusada(Auth::user()));
        if($visitas->isEmpty()){//se o usuário não tem visita cadastrada,deleta ele e a conta
	   if($user->delete()){
	      return redirect()->route('auth.acesso_gerenciamento')->with('success','Solicitação recusada com sucesso');
           }
        }
        //se o usuário tem visitas cadastradas no sistema,só atualiza ele novamente como visitante e deleta a conta 
        $dados=[
            'user_description' =>null,
            'avatar' => null,
            'user_type' => 'visitant',
            'link_lattes'=>null,
            'slug'=>null,
        ];
        $user->update($dados);
        $conta->delete();
	return redirect()->route('auth.acesso_gerenciamento')->with('success','Solicitação recusada com sucesso');
    }

    public function editar(){
	    return view('auth.registros.editar');
    }
   
    public function atualizar(RegisterRequest $data)//retirar verificação de senha
    {  
        $data->validated();
        $dados = $data->all();
	$user=Auth::user();
        $dados['slug']=str_slug($dados['name']).'-'.$user->id;
        if($data->hasFile('avatar')){
            $anexo = $data->file('avatar');
            $dir = 'img/avatares/';
            $exAnexo =$anexo->guessClientExtension();
            $nomeAnexo = $nomeAnexo = 'avatar_'.$user['slug'].'.'.$exAnexo;
            $anexo->move($dir, $nomeAnexo);
            $dados['avatar'] = $dir.'/'.$nomeAnexo;
           
        }
        
        $user->update($dados);
        
	    return redirect()->route('auth.registros')->with('success','Conta editada com sucesso');
         
    }

    public function deletar($id_user){
        $data=$this->usuario->find($id_user);

        Auth::logout();

        if ($data->delete()) {
            return redirect()->route('register')->with('success','Conta exluida com sucesso');
        }
    }

    public function siteIndex(){
        $usersAdmin=$this->usuario->where( 'user_type', 'admin')->get();
        $registros= $usersAdmin->whereNotNull('cpf_verified_at')->all();

        $contato = $this->contato->latest('updated_at')->first();

        return view('site.contato.index', compact('registros', 'contato'));
    }

    public function siteRegistervizualizar(User $registro){
        return view('site.contato.vizualizar', compact('registro'));
    }

    public function buscarUsuarioVisita(EmailVisitaRequest $request)
    {  
        $request->validated();
        $email = $request['email'];
        $userExiste = $this->usuario->where('email', $email)->first();
        $horas = $this->visita->horas;
        $visitas = $this->visita->where('confirmada', true)->select('data', 'hora_inicial', 'hora_final')->get();

        foreach($visitas as $visita) {
            $visita->data = date('d/m/Y', strtotime($visita->data));
            $visita->hora_inicial = str_replace('.00', '', date('H.i', strtotime($visita->hora_inicial)));
            $visita->hora_final = str_replace('.00', '', date('H.i', strtotime($visita->hora_final)));
        }
        
	    return view('site.visitas.adicionar', compact('userExiste', 'email', 'horas', 'visitas'));
    }
    public function vizualizarTermosPrivacidade (){
    	return view('auth.privacidade_termos');
    }
}
