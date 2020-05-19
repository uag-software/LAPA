@extends('layouts.app')

@section('titulo', 'Materiais')
@section('content')
<div class="d-flex position-relative">

    @if (count($paginas) >= 1)

        @include('site.materiais._sidebar')

    @endif

    <div id="page" class="container col-lg-9">    
        <h2>Material</h2>

        @if (count($paginas) < 1)
            <p>Ops, essa categoria ainda não possui páginas</p>
        @else

            @foreach ($paginas as $pagina)
                <div class="row justify-content-center">
                    <div class="col-12 col-md-10 col-lg-9 text-left mb-4">
                        <h3 class="title">{{ $pagina->titulo }}</h3>
                        <p class="text">
                            {{ $pagina->texto }}
                        </p>
                        <p class="text-footer">
                            Publicado em {{ date('d/m/Y', strtotime($pagina->created_at)) }} às {{ date('H:i', strtotime($pagina->created_at)) }}
                        </p>
                        <div class="text-center">
                            <a class="btn" href="{{ asset($pagina->anexo) }}" alt="" download="baixar">Baixar anexo</a>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-center">
                    {{ $paginas->links() }}
                </div>
            @endforeach
        
        @endif
        
    </div>
</div>
@endsection

@section('scripts')
    <script src="{{ asset('js/toggle_sidebar.js') }}"></script>
@endsection
