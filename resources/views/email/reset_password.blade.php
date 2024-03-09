<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pesan Masuk</title>
    {{-- <link rel="stylesheet" href="{{ asset('css/style.css') }}"> --}}

    <style>
        body {
            background-color: grey;
        }
    </style>
</head>

<body>

    <div class="container">
        <header>
            <h1>PT ACTEEVE INDONESIA</h1>
        </header>
        <section>
            <h3>Hallo, {{ $user->name }}</h3>
            <p><strong>Selamat <br> Password Akun Anda Telah Berhasil Diubah.</strong></p>
            <p><strong>Email : {{ $user->email }}</strong></p>
            <p><strong>Password Terbaru : {{ $user->passwordRecovery }}</strong></p>
            <p><Strong>Link Website : </Strong> <a href="https://acteeveallthingsfinance.id/login"
                    style="text-decoration: none; font-size:13px; font-weight:bold; color:blue;">acteeveallthingsfinance.id</a>
            </p>
            <p><strong>Terimakasih <br> Sudah mengguanakan Jasa layanan kami. <br> <br>

                    <h3>Hormat Kami <br><br> PT ACTEEVE INDONESIA</h3>

        </section>
        <footer>
            <br>
            <p> <strong>Terima kasih</strong></p>
        </footer>
    </div>

</body>

</html>
