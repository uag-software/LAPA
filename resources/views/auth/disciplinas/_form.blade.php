<div class="form-group">
    <label for="nome">Nome da disciplina *</label>
    <input {{ isset($registro->nome) ? 'readonly' : '' }} class="form-control form-control-lg @error('nome') is-invalid @enderror" type="text" name="nome" value="{{ isset($registro->nome) ? $registro->nome : old('nome') }}" placeholder="Digite aqui o nome da disciplina">
    @error('nome')
        <span class="invalid-feedback" role="alert">
            <strong>{{ $message }}</strong>
        </span>
    @enderror
</div>
<div class="form-group">
    <label for="user_id">Selecione o professor</label>
    <select class="form-control form-control-lg" name="user_id" id="professores">
        <option hidden disabled selected value>{{ __('Selecione um professor') }}</option>
        <option value>{{ __('Nenhum') }}</option>
        @foreach($users as $user)
            @if(isset($registro->user->id) && $user->id = $registro->user->id)
                <option value="{{ $user->id }}" selected>{{ $user->name }}</option>
            @else
                <option value="{{ $user->id }}">{{ $user->name }}</option>
            @endif
        @endforeach
    </select>
</div>