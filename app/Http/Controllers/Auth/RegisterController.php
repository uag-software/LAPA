<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use App\User;
use Illuminate\Foundation\Auth\RegistersUsers;
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
use Illuminate\Http\File;
use Illuminate\Support\Facades\Storage;
use Image;
use Symfony\Component\HttpFoundation\File\UploadedFile;
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
  
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    
     public function __construct(User $usuario, Visita $visita,Conta $conta) 
    {
       $this->middleware('auth', ['except' => [

            'register','showRegistrationForm','siteIndex','siteRegistervizualizar', 'buscarUsuarioVisita']]);
       $this->middleware('guest', ['only' => [
            'register',
            'showRegistrationForm'
        ]]);
      
       $this->usuario=$usuario;
       $this->visita = $visita;
       $this->conta=$conta;
       
       
    }

    protected function validator(array $data)
    {
        return Validator::make($data, [
            'name' =>'required|alpha|string|min:3|max:255',
	        'surname' =>'required|alpha|string|min:3|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
	        'cpf' => 'required|regex:/\d{3}\.\d{3}\.\d{3}\-\d{2}/|string|unique:users',
            'user_description' => 'max:255|nullable',
            'link_lattes' => 'url|string|nullable',
            'avatar' => 'mimes:jpeg,jpg,png,gif|max:2048|nullable' 
            
        ]);
       
    }
   
    /**
     * Create a new user instance after a valid registration.
     *
     * @param  array  $data
     * @return \App\User
     */
     
    protected function create(array $data)
    { 
       $usersAdmin=$this->usuario->where( 'user_type', 'admin')->get();
       $registros= $usersAdmin->whereNotNull('cpf_verified_at')->all();
       $avatar=null;
       $request = new Request($data);
       if($request->has('avatar')) {
            $anexo = $data['avatar'];
            $num = rand(1111,9999);
            $dir = 'img/avatares/';
            $exAnexo =$anexo->guessClientExtension();
            $nomeAnexo = 'avatar_'.$num.'.'.$exAnexo;
            $anexo->move($dir, $nomeAnexo);
            $avatar= $dir.'/'.$nomeAnexo;
           
        }

       $user= $this->usuario->create([
            'name' =>  $data ['name'],
            'cpf' =>  $data ['cpf'],
            'email' =>  $data['email'],
            'surname' =>  $data ['surname'],
            'user_description' =>  $data ['user_description'],
            'avatar' => $avatar,
            'user_type' => 'admin',
            'link_lattes'=> $data['link_lattes'],
        ]);

        $this->conta->create([
            'password' => Hash::make( $data ['password']),
            'user_id'=>$user->id,  
        ]);

        $user['slug']=str_slug($user->name).'-'.$user->id;
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
        $usersAdmin=$this->usuario->where( 'user_type', 'admin')->get();
        $registros= $usersAdmin->where('cpf_verified_at',null)->all();
        return view('auth.acesso_gerenciamento', compact('registros'));
    }

    public function aceitarSolicitacao($id_user){//lembrar de enviar notificação
        $user=$this->usuario->find($id_user);
        $user['cpf_verified_at']=now();
        $dados=[$user];
	    $user->update($dados);
        Notification::send($user,new SolicitacaoAcesso_aceita(Auth::user()));
        return redirect()->route('auth.acesso_gerenciamento')->with('success','Solicitação confrimada com sucesso'); 
    }

    public function recusarSolicitacao($id_user){
        $user=$this->usuario->find($id_user);
        Notification::send($user,new SolicitacaoAcesso_recusada(Auth::user()));
        if($user->delete()){
	        return redirect()->route('auth.acesso_gerenciamento')->with('success','Solicitação recusada com sucesso');
        }
    }

    public function editar(){
	    return view('auth.registros.editar');
    }
   
    public function atualizar(RegisterRequest $data)//retirar verificação de senha
    {  
        $data->validated();
        $dados = $data->all();
        if($data->hasFile('avatar')){
            $anexo = $data->file('avatar');
            $num = rand(1111,9999);
            $dir = 'img/avatares/';
            $exAnexo =$anexo->guessClientExtension();
            $nomeAnexo = 'avatar_'.$num.'.'.$exAnexo;
            $anexo->move($dir, $nomeAnexo);
            $dados['avatar'] = $dir.'/'.$nomeAnexo;
           
        }
        $user=Auth::user();
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
       
        return view('site.quemSomos.index', compact('registros'));
    }

    public function siteRegistervizualizar(User $registro){
        return view('site.quemSomos.vizualizar', compact('registro'));
    }

    public function buscarUsuarioVisita(EmailVisitaRequest $request)
    {  
        $request->validated();
        $email = $request['email'];
        $userExiste = $this->usuario->where('email', $email)->first();
        
	    return view('site.visitas.adicionar', compact('userExiste', 'email'));
    }
  
}
