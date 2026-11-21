<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>SICLIN · Estudio de decisión</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; }
        table { border-collapse: collapse; width: 100%; table-layout: fixed; }
        td { padding: 6px; border: 1px solid #bbb; word-wrap: break-word; }
        h2 { font-size: 13px; margin-top: 18px; }
    </style>
</head>
<body>
    <h1>SICLIN · Estudio de decisión</h1>
    @foreach($sections as $title => $rows)
        <h2>{{ $title }}</h2>
        <table>
            @foreach($rows as $row)
                <tr>@foreach($row as $cell)<td>{{ $cell }}</td>@endforeach</tr>
            @endforeach
        </table>
    @endforeach
</body>
</html>
