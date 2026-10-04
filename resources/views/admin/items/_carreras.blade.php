<div class="mb-3">
    <label class="form-label">Carreras / áreas</label>
    <div class="form-text mt-0 mb-2">Deja todo sin marcar si el ítem es para todas las carreras. Marca una o más si es un requisito específico (ej. Medicina: bata, pijama).</div>
    <div class="row">
        @foreach($carreras as $carrera)
            <div class="col-md-4">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="carreras[]" value="{{ $carrera->id }}" id="carrera{{ $carrera->id }}"
                           @checked(in_array($carrera->id, old('carreras', $seleccionadas ?? [])))>
                    <label class="form-check-label" for="carrera{{ $carrera->id }}">{{ $carrera->nombre }}</label>
                </div>
            </div>
        @endforeach
    </div>
</div>
