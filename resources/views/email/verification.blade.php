<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pesan Masuk</title>
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
</head>

<body>

    <div class="container">
        <header>
            <h1>PT ACTEEVE INDONESIA</h1>
        </header>
        <section>
            <h3>Hallo, {{ $user->user }}</h3>
            <p><strong>Selamat <br> Registrasi akun anda Telah Berhasil.</strong></p>
            <p style="text-align:center;"><strong>Email : {{ $user->email }}</strong></p>
            <p style="text-align:center;"><strong>Password : {{ $user->passwordRecovery }}</strong></p>
            <p><strong>Terimakasih <br> Sudah mengguanakan layanan kami. <br> <br>
                    <h3 style="color: white;">Hormat Kami <br><br> PT ACTEEVE INDONESIA</h3>
        </section>
        <footer>
            <p>Terima kasih</p>
        </footer>
    </div>

</body>

</html>
