<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    Hello {{ $sayHay }}

    <table>
        <tr>
            <td>
                @foreach ($siswas as $siswa)
                    <p>{{ $siswa->nama }} ({{ $siswa->nis }})</p>
                @endforeach
            </td>
        </tr>
    </table>
</body>
</html>