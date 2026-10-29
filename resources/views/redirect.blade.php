<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Redirecționare spre plată</title>
</head>
<body>
    <p>Te redirecționăm spre pagina de plată…</p>

    <x-payments::auto-submit-form :redirect="$redirect" />
</body>
</html>
