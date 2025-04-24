<!DOCTYPE html>
<html lang="en">

<head>
    <?php
    include "inc/head.inc.php";
    ?>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Just My Cup Of Tea | Contact Us</title>

    <!--
          - custom css link
        -->
    <link rel="stylesheet" href="./css/contact-us.css">

    <!--
          - google font link
        -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">

</head>

<body>
    <!-- Navbar -->
    <?php
    include "inc/nav.inc.php";
    ?>
    <main>
        <div class="main-content">

            <!--
                  - #CONTACT
-->
            <header>
                <h2 class="h2 article-title">Contact Us</h2>
            </header>

            <div class="mapbox" data-mapbox>
                <figure>
                    <iframe id="4aac400a-b9c7-4c3f-bf87-b0b5c889dd12"
                        src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d2472.3517134681547!2d103.91201359426614!3d1.413773432982798!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x31da1515bfb2d263%3A0xc71c56458ac08497!2sSingapore%20Institute%20of%20Technology%20(Campus%20Court)!5e0!3m2!1sen!2ssg!4v1741322562631!5m2!1sen!2ssg"
                        width="400" height="300" loading="lazy"
                        title="Map showing the location of Singapore Institute of Technology (Campus Court)">
                    </iframe>
                </figure>
            </div>

            <section class="contact-form">

                <h3 class="h3 form-title">Contact Form</h3>

                <form action="#" class="form" data-form>

                    <div class="input-wrapper">
                        <input type="email" id="email" name="email" class="form-input" placeholder="Email Address"
                            required data-form-input>

                    </div>

                    <textarea name="message" id="message" class="form-input" placeholder="Your Message" required
                        data-form-input></textarea>

                    <button class="form-btn" type="submit" data-form-btn>
                        <ion-icon name="paper-plane"></ion-icon>
                        <span>Send Message</span>
                    </button>

                </form>

            </section>

        </div>
    </main>

    <?php
    include "inc/footer.inc.php";
    ?>
</body>


</html>