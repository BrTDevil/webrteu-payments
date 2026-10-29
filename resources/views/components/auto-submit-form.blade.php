@props(['redirect', 'label' => 'Continuă spre plată'])

<form id="payments-auto-submit-form" method="POST" action="{{ $redirect->url }}">
    @foreach ($redirect->fields as $name => $value)
        <input type="hidden" name="{{ $name }}" value="{{ $value }}">
    @endforeach

    <button type="submit">{{ $label }}</button>
</form>

<script>document.getElementById('payments-auto-submit-form').submit();</script>
