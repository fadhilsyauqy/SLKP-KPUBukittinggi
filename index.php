<?php
  session_start();
  include("koneksi.php");

  ?>


  <!DOCTYPE html>
  <html lang="en">

  <head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">

    <title>Sistem laporan Kinerja Pegawai Sekretariat </title>
    <meta content="" name="description">
    <meta content="" name="keywords">

    <!-- Favicons -->
    <link href="img/logo-kpu.png" rel="icon">
    <link href="img/logo-kpu.png" rel="logokpu">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,300i,400,400i,600,600i,700,700i|Raleway:300,300i,400,400i,500,500i,600,600i,700,700i|Poppins:300,300i,400,400i,500,500i,600,600i,700,700i" rel="stylesheet">

    <!-- Vendor CSS Files -->
    <link href="assets/vendor/animate.css/animate.min.css" rel="stylesheet">
    <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/vendor/boxicons/css/boxicons.min.css" rel="stylesheet">
    <link href="assets/vendor/glightbox/css/glightbox.min.css" rel="stylesheet">
    <link href="assets/vendor/remixicon/remixicon.css" rel="stylesheet">
    <link href="assets/vendor/swiper/swiper-bundle.min.css" rel="stylesheet">

    <!-- Template Main CSS File -->
    <link href="assets/css/style.css" rel="stylesheet">


  </head>

  <body>

    <!-- ======= Header ======= -->
    <header id="header" class="fixed-top d-flex align-items-center">
      <div class="container d-flex align-items-center">

        <h1 class="logo me-auto ">
          <img src="img/label-kpu.png" width="300px" height="100px" alt="logo">
        </h1>
        <!-- Uncomment below if you prefer to use an image logo -->
        <nav id="navbar" class="navbar">
          <ul>
            <li><a href="#hero" >Home</a></li>
            <li><a href="#visi">Visi Misi</a></li>
            <li><a href="#about">About</a></li>
            <li><a href="karyawan/hadir" class="getstarted">Laporkan Kinerja</a></li>
            <li><a href="login" target="_blank">  <i class="bi bi-person-circle" style="float: left; margin-right: 6px; font-size: 20px"></i></a></li>
            </li>
          </ul>
          <i class="bi bi-list mobile-nav-toggle"></i>
        </nav><!-- .navbar -->

      </div>
    </header><!-- End Header -->

    <!-- ======= Hero Section ======= -->
    <section id="hero">
      <div id="heroCarousel" data-bs-interval="5000" class="carousel slide carousel-fade" data-bs-ride="carousel">

        <ol class="carousel-indicators" id="hero-carousel-indicators"></ol>

        <div class="carousel-inner" role="listbox">

          <!-- Slide 1 -->
          <div class="carousel-item active" style="background-image: url(assets/img/slide/bg-slide1.jpg)">
            <div class="carousel-container">
              <div class="container">
                <!--<h2 class="animate__animated animate__fadeInDown">Welcome to <span>Sistem Laporan Kinerja Pegawai</span></h2>-->
                <!--<p class="animate__animated animate__fadeInUp">Merupakan aplikasi absensi berbasis website yang diperuntukan karyawan KPU Kota Bukittinggi.</p>-->
                <!--<a href="karyawan/hadir" class="btn-get-started animate__animated animate__fadeInUp scrollto">Laporkan Kinerja</a>-->
              </div>
            </div>
          </div>

          <!-- Slide 2 -->
          <div class="carousel-item" style="background-image: url(assets/img/slide/bg-slide2.jpg)">
            <div class="carousel-container">
              <div class="container">
                <!--<h2 class="animate__animated animate__fadeInDown" >E-KINERJA</span></h2>-->
                <!--<h2 class="animate__animated animate__fadeInDown">SEKRETARIAT KOTA BUKITTINGGI</span></h2>-->
                <!--<p class="animate__animated animate__fadeInUp">Laporkan setiap tugas Anda dengan teliti dan tepat waktu untuk kesuksesan bersama di KPU Kota Bukittinggi</p>-->
                <!--<a href="karyawan/hadir" class="btn-get-started animate__animated animate__fadeInUp scrollto">Laporkan Kinerja</a>-->
              </div>
            </div>
          </div>

          <!-- Slide 3 -->
          <div class="carousel-item" style="background-image: url(assets/img/slide/bg-slide3.jpg)">
            <div class="carousel-container">
              <div class="container">
                <!--<h2 class="animate__animated animate__fadeInDown">Disiplin adalah Kunci</h2>-->
                <!--<h2 class="animate__animated animate__fadeInDown">Tingkatkan Produktivitas</h2>-->
                <!--<p class="animate__animated animate__fadeInUp">Disiplin dalam mengisi laporan harian memastikan kita terus maju dengan langkah yang tepat.</p>-->
                <!--<a href="karyawan/hadir" class="btn-get-started animate__animated animate__fadeInUp scrollto" id="btn-get-started">Laporkan Kinerja</a>-->
              </div>
            </div>
          </div>

        </div>

        <a class="carousel-control-prev" href="#heroCarousel" role="button" data-bs-slide="prev">
          <span class="carousel-control-prev-icon bi bi-chevron-left" aria-hidden="true"></span>
        </a>

        <a class="carousel-control-next" href="#heroCarousel" role="button" data-bs-slide="next">
          <span class="carousel-control-next-icon bi bi-chevron-right" aria-hidden="true"></span>
        </a>

      </div>
    </section><!-- End Hero -->

    <main id="main">

      <!-- ======= Footer ======= -->
      <footer id="footer">
        <div class="row" id="visi">
        </div>
        </div>
        

      </footer><!-- End Footer -->

      <!-- ======= About Section ======= -->
      <section  class="about">
        <div class="container">
          <div class="row content">
            <div class="text-center">
              <h3 style="font-weight: bold;">Visi Komisi Pemilihan Umum</h3>
              <p style="font-size: 17px">“Menjadi Penyelenggara Pemilihan Umum yang Mandiri, Profesional, dan Berintegritas untuk Terwujudnya Pemilu yang LUBER dan JURDIL.”</p>
              <h3 style="font-weight: bold;" class="mt-5">Misi Komisi Pemilihan Umum</h3>
              <ul class="text-center" style="font-size: 17px">
                <li>1. Meningkatkan kualitas penyelenggaraan Pemilu yang efektif dan efisien, transparan, akuntabel, serta aksesibel.</li>
                <li>2. Meningkatkan kualitas, integritas, kemandirian, kompetensi, dan profesionalisme penyelenggara Pemilu.</li>
                <li>3. Menyusun regulasi di bidang Pemilu yang memberikan kepastian hukum, progresif, dan partisipatif.</li>
                <li>4. Meningkatkan kualitas pelayanan Pemilu untuk seluruh pemangku kepentingan.</li>
                <li>5. Meningkatkan partisipasi dan kualitas pemilih dalam Pemilu, Pemilih berdaulat Negara dengan kuat.</li>
                <li>6. Mengoptimalkan pemanfaatan teknologi informasi dalam penyelenggaraan Pemilu.</li>
              </ul>
            </div>
      </section><!-- End About Section -->

      <!-- ======= Footer ======= -->
      <footer id="footer">

        <div class="row">


        </div>

        </div>
        </div>
        </div>

      </footer><!-- End Footer -->

      <!-- ======= Contact Section ======= -->
      <section id="about" class="contact">
        <div class="container">

          <div class="row mt-5">
            <div class="col-lg-6">
              <div class="info">
                <div class="address">
                  <i class="bi bi-geo-alt"></i>
                  <h4>Location:</h4>
                  <p>Jl. Cindua Mato No.7, Benteng Ps. Atas, Kec. Guguk Panjang, Kota Bukittinggi, Sumatera Barat 26136</p>
                </div>

                <div class="email">
                  <i class="bi bi-envelope"></i>
                  <h4>Email:</h4>
                  <p>kpubukittinggikota@gmail.com</p>
                </div>

                <div class="phone">
                  <i class="bi bi-phone"></i>
                  <h4>Call:</h4>
                  <p>(0752) 22782</p>
                </div>

              </div>
            </div>



            <div class="col-lg text-center">
              <h2>Sistem Laporan Kinerja Harian</h2>
              <h3 style="margin-top: 1rem;">KPU Kota Bukittinggi</h3>
              <img src="img/logo-kpu.png" style="border:0; width: 200px; height: 200px; margin-top: 1rem;">
            </div>
          </div>

        </div>

        </div>
      </section><!-- End Contact Section -->

    </main><!-- End #main -->

    <!-- ======= Footer ======= -->
    <footer id="footer">

      <div class="container">
        <div class="row">

        </div>
      </div>
      </div>

      <div class="container">
        <div class="copyright">
          &copy; Copyright <strong><span>KPU Kota Bukittinggi</span></strong>. All Rights Reserved
        </div>
        <div class="credits">
          <!-- All the links in the footer should remain intact. -->
          <!-- You can delete the links only if you purchased the pro version. -->
          <!-- Licensing information: https://bootstrapmade.com/license/ -->
          <!-- Purchase the pro version with working PHP/AJAX contact form: https://bootstrapmade.com/sailor-free-bootstrap-theme/ -->
          Designed by <a href="">KPU Kota Bukittinngi</a>
        </div>
      </div>
    </footer><!-- End Footer -->

    <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

    <!-- Vendor JS Files -->
    <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="assets/vendor/glightbox/js/glightbox.min.js"></script>
    <script src="assets/vendor/isotope-layout/isotope.pkgd.min.js"></script>
    <script src="assets/vendor/swiper/swiper-bundle.min.js"></script>
    <script src="assets/vendor/waypoints/noframework.waypoints.js"></script>
    <script src="assets/vendor/php-email-form/validate.js"></script>

    <!-- Template Main JS File -->
    <script src="assets/js/main.js"></script>

  </body>

  </html>