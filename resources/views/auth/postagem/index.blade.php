@extends('layouts.app')

@section('titulo', 'Gerenciar postagens')
@section('content')
        <div class="container">
            <h2>Gerenciar postagens</h2>
            <a href="{{ route('auth.postagem.adicionar') }}" class="btn mb-2">Adicionar</a>
            @if(Session::has('success'))
                <div class="alert alert-success alert-dismissible">
                    {{ Session::get('success') }}
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            @endif  
            <div class="d-sm-flex justify-content-between">
                @if(isset($filtros['publicado']))
                    <p>Mostrando páginas por <strong>{{ $filtros['nomes'][1] ?? ''}}</strong> <strong>{{ $filtros['nomes'][0] ?? '' }}</strong>
                        <a class="btn btn-link align-self-start" href="{{ route('auth.postagens') }}">(Remover filtros)</a>
                    </p>
                @elseif(isset($filtros['tipo_postagem']))
                    <p>Mostrando páginas por <strong>{{ $filtros['nomes']?? ''}}</strong>
                        <a class="btn btn-link align-self-start" href="{{ route('auth.postagens') }}">(Remover filtros)</a>
                    </p>
                @endif

                <div class="dropdown ml-auto mb-3">
                    <button class="btn dropdown-toggle" type="button" id="filtrarDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        Filtrar por
                    </button>
                    <div class="dropdown-menu dropdown-menu-right p-2 text-center" aria-labelledby="filtrarDropdown" style="min-width: 300px;">
                        <form action="{{ route('auth.postagens') }}" method="get">
                            @csrf  
                            <div class="form-group mb-2">
                                <label for="">Status</label>
                                <select size="3" class="form-control" name="publicado" id="publicado">
                                    <option value="" {{ !isset($filtros['publicado']) ? 'selected' : '' }}>Todos</option>
                                    <option value="1" {{ isset($filtros['publicado']) && ($filtros['publicado'] == 1) ? 'selected' : '' }}>Publicados</option>
                                    <option value="0" {{ isset($filtros['publicado']) && ($filtros['publicado'] == 0) ? 'selected' : '' }}>Rascunhos</option>
                                </select>
                            </div>
                            <div class="form-group mb-2">
                                <label for="">Tipo da postagem</label>
                                <select size="3" class="form-control" name="tipo_postagem" id="tipo_postagem">
                                    <option value="" {{ !isset($filtros['tipo_postagem']) ? 'selected' : '' }}>Todas</option>
                                    @foreach ($tipo_postagens as $tipo)
                                        <option {{ isset($filtros['tipo_postagem']) && ($filtros['tipo_postagem'] == $tipo) ? 'selected' : '' }} value="{{$tipo}}">{{ $tipo}}</option>
                                    @endforeach
                                    
                                </select>
                            </div>
                            
                            <button class="btn align-self-start" type="submit">Filtrar</button>
                        </form>
                    </div>
                </div>
            </div>    
            <div class="table-responsive">
              <table class="table table-bordered" id="myTable">
                <thead class="thead-primary">
                    <tr>
                        <th>Ações</th>
                        <th>Título</th>
                        <th>Autor(ª)</th>
                        <th>Tipo da postagem</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                  @if(isset($registros) && count($registros) > 0)
                    @foreach($registros as $registro)
                      <tr>
                        <td>
                            <a href="{{ route('auth.postagem.editar', $registro->slug) }}" class="btn">Editar</a>
                            <a href="{{ route('auth.postagem.deletar', $registro->slug) }}" class="btn btn-danger" onclick="return confirm('Tem certeza que deseja deletar essa postagem?');">Deletar</a>
                        </td>
                        <td>{{ $registro->titulo }}</td>
                        <td>{{ isset($registro->user) ? $registro->user->name : 'Nenhum professor' }}</td>
                        <td>{{ $registro->tipo_postagem }}
                        <td>{{$registro->publicado ? 'Publicado' : 'Rascunho'}}</td>
                    </tr>
                    @endforeach
                  @else
                        <tr>
                            <td colspan="4">Nenhum resultado em postagem encontrado</td>
                        </tr>
                  @endif
                </tbody>
              </table>
          </div>
        </div>
@endsection
@section('scripts')

    <script>  
      $(document).ready( function () {
      $('#myTable').DataTable( {
      "columnDefs": [
      { "orderable": false, "targets":'_all'}
      ],
      "language": {
      "url": "//cdn.datatables.net/plug-ins/1.10.19/i18n/Portuguese-Brasil.json"
      }
      } );
      } );
    
    </script>
    <script>
            $(function() {
                $()
            });
    </script>
@endsection