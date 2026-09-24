@props([
    'name' => 'password',
    'placeholder' => 'Minimal 8 karakter',
    'autocomplete' => 'new-password',
    'required' => false,
    'dataRequiredOnCreate' => false,
    'class' => '',
])

<div class="relative">
    <input type="password" name="{{ $name }}" minlength="8" autocomplete="{{ $autocomplete }}" @if ($required) required @endif @if ($dataRequiredOnCreate) data-required-on-create @endif class="h-11 w-full rounded-lg border border-line px-3 pr-12 font-normal {{ $class }}" placeholder="{{ $placeholder }}">
    <button type="button" data-password-toggle class="absolute right-1.5 top-1/2 grid size-9 -translate-y-1/2 place-items-center rounded-lg text-slate-400 hover:bg-slate-100 hover:text-slate-700 focus-visible:outline-2 focus-visible:outline-primary-600" aria-label="Tampilkan password">
        <span data-password-show><x-icon name="eye" /></span>
        <span data-password-hide class="hidden"><x-icon name="eye-slash" /></span>
    </button>
</div>
