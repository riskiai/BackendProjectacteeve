<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pesan Masuk</title>
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <style>
        body {
            background-color: grey;
        }
    </style>
</head>
<body>

    <div class="container"> 
        <header>
            <h1>PT ACTEEVE</h1>
        </header>
        <section>
            <h3>Hallo, Riski Ahmad Ilham</h3>
            <p ><strong>Selamat <br> Registrasi akun anda Telah Berhasil.</strong></p>
            <p><strong>Email : riskiahmadiilham@gmail.com</strong></p>
            <p ><strong>Password : 1234567</strong></p>
            <p><strong>Terimakasih <br> Sudah mengguanakan layanan kami. <br> <br>
            <h3 >Hormat Kami <br><br> PT ACTEEVE</h3>
            <p>Verifikasi Email</p>
            {{-- <p><strong>Name:</strong> {{ $mailData['name'] }}</p>
            <p><strong>Email:</strong> {{ $mailData['email'] }}</p>
            <p><strong>Phone:</strong> {{ $mailData['phone'] }}</p>
            <p><strong>Message:</strong> {{ $mailData['message'] }}</p> --}}
        </section>
        <footer>
            <h3>Terimakasih</h3>
        </footer>
    </div>

</body>
</html>
