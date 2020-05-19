<div class="form-group">
    <label for="titulo">Título da Postagem*</label>
    <input class="form-control form-control-lg @error('titulo') is-invalid @enderror" type="text" name="titulo" value="{{ isset($registro->titulo) ? $registro->titulo : old('titulo') }}" placeholder="Digite aqui o titulo da postagem" required autocomplete="titulo">
    @error('titulo')
        <span class="invalid-feedback" role="alert">
            <strong>{{ $message }}</strong>
        </span>
    @enderror
</div>
<div class="form-group">
    <label for="descricao">Descrição*</label>
    <textarea id="summernote" class="form-control form-control-lg @error('descricao') is-invalid @enderror" type="text" name="descricao" placeholder="Descreva aqui a descrição da sua postagem" required autocomplete="descricao">{{ isset($registro->descricao) ? $registro->descricao : old('descricao') }}</textarea>
    @error('descricao')
        <span class="invalid-feedback" role="alert">
            <strong>{{ $message }}</strong>
        </span>
    @enderror
</div>
<div class="form-group">
    <label for="tipo_postagem">Selecione o tipo da postagem*</label>
    <select class="form-control form-control-lg @error('tipo_postagem') is-invalid @enderror" name="tipo_postagem" id="tipo_postagem" required autocomplete="tipo_postagem">
        @if(isset($registro->tipo_postagem))
                <option value="{{ $registro->tipo_postagem }}" selected>{{$registro->tipo_postagem}}</option>
        @else
        <option hidden disabled selected value>selecionar</option>
        @endif
        @foreach($tipo_postagens as $tipo)
           
            <option value="{{ $tipo}}">{{ $tipo}}</option>
          
        @endforeach
    </select>
     @error('tipo_postagem')
        <span class="invalid-feedback" role="alert">
            <strong>{{ $message }}</strong>
        </span>
    @enderror
</div>
<div class="form-group">
    <label for="nome">Anexo</label>
    <input class="form-control form-control-lg @error('anexo') is-invalid @enderror" id="anexo" type="file" name="anexo"  autocomplete="anexo">
    @error('anexo')
              <span class="invalid-feedback" role="alert">
                 <strong>{{ $message }}</strong>
             </span>
    @enderror
</div>

@if(@isset($registro->anexo))
    <div class="form-group">
        <img src="{{ asset($registro->anexo) }}" alt="">
    </div>    
@endisset

<div class="d-flex flex-row">
    <div class="form-group">
        <label for="data">Data(obrigatório para evento)</label>
        <input min="{{ date('Y-m-d', strtotime('tomorrow')) }}" class="form-control form-control-lg @error('data') is-invalid @enderror" type="date" name="data" value="{{ isset($registro->data) ? $registro->data : old('data') }}"autocomplete="data">
        @error('data')
            <span class="invalid-feedback" role="alert">
                <strong>{{ $message }}</strong>
            </span>
        @enderror
    </div>
</div>
<div class="d-flex flex-row">
     <div class="form-group">
        <label for="hora">Hora(obrigatório para evento)</label>
        <input min="{{ date('H:i', strtotime('00:00')) }}" class="form-control form-control-lg @error('hora') is-invalid @enderror" type="time" name="hora" value="{{ isset($registro->hora) ? $registro->hora : old('hora') }}" placeholder="Digite a hora do evento">
        @error('hora')
        <span class="invalid-feedback" role="alert">
            <strong>{{ $message }}</strong>
        </span>
        @enderror
    </div>
</div>
<label class="input-checkbox" for="publicado">Publicar agora?
    <input type="checkbox" name="publicado" autocomplete="publicado" {{ isset($registro->publicado) && $registro->publicado == true ? 'checked' : ''}} value="true">
    <span class="checkmark"></span>
</label>
