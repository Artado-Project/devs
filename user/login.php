<?php

require_once '../includes/database.php';
require_once '../includes/auth.php';
require_once '../includes/session_start.php';


// Eğer kullanıcı zaten giriş yaptıysa, yönlendirme yap.
if (isset($_SESSION['user_email'])) {
    // Kullanıcının rolünü al
    $user_role = getUserRole($_SESSION['user_email'], $db); // getUserRole fonksiyonunu auth.php'de tanımlamanız gerekiyor

    // Kullanıcı adminse admin sayfasına, kullanıcıysa user sayfasına yönlendir.
    if ($user_role === 'admin') {
        header("Location: ../user"); // admin sayfasına yönlendir
    } else {
        header("Location: ../user"); // user sayfasına yönlendir
    }
    exit();
}

if (isset($_GET['logout'])) {
    logout();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    if (login($email, $password, $db)) {
        // Kullanıcının rolünü veritabanından al
        $user_role = getUserRole($email, $db);

        // Kullanıcıyı oturum açarak yönlendir.
        $_SESSION['user_email'] = $email;

        if ($user_role === 'admin') {
            $_SESSION['success'] = "Admin girişi başarılı!";
            header("Location: ../user"); // admin sayfasına yönlendir
        } else {
            $_SESSION['success'] = "Giriş başarılı!";
            header("Location: ../user"); // user sayfasına yönlendir
        }
        exit();
    } else {
        $error = "Geçersiz e-posta veya şifre.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giriş Yap</title>
    <link rel="stylesheet" href="css/style1.css">
    <link rel="icon" type="image/x-icon" href="/public/uploads/files/favicon.ico">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <style>
        /* fonts  */

        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');


        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background: #1c1c1c;
            background-repeat: no-repeat;
            background-position: center;
            background-size: cover;
        }

        .container {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }

        .box {
            background: #fdfdfd;
            display: flex;
            flex-direction: column;
            padding: 35px 35px;
            border-radius: 20px;
            box-shadow: 0 0 128px 0 rgba(0, 0, 0, 0.1), 0 32px 64px -48px rgba(0, 0, 0, 0.5);
        }

        .form-box {
            width: 450px;
            margin: 50px 10px;
        }

        .form-box header {
            font-size: 40px;
            font-weight: 600;
            text-align: center;
            color: #2B547E;
        }

        .form-box hr {
            background-color: #2B547E;
            height: 5px;
            width: 20%;
            border: none;
            margin: 5px auto;
            outline: 0;
            border-radius: 5px;
        }

        .input-container {
            display: flex;
            width: 80%;
            margin-bottom: 15px;
        }

        .icon {
            padding: 15px;
            background: transparent;
            color: #555;
            background-color: #f1f1f1;
            min-width: 50px;
            text-align: center;
            cursor: pointer;
        }

        .input-field {
            width: 100%;
            padding: 10px;
            height: 50px;
            outline: none;
            border: none;
            font-size: 15px;
            background-color: #f1f1f1;
        }

        .input-field:focus {
            color: #2B547E;
        }

        .remember {
            display: flex;
            font-size: 15px;
            margin-bottom: 50px;
            margin-top: 20px;
        }

        .remember .check {
            margin-right: 5px;
        }

        .remember span {
            margin-left: 105px;
        }

        .remember span a {
            text-decoration: none;
            color: #2B547E;
        }

        .remember span a:hover {
            font-weight: bold;
        }

        .btn {
            height: 45px;
            width: 80%;
            background-color: #262626;
            border: 0;
            border-radius: 5px;
            color: #fff;
            font-size: 18px;
            cursor: pointer;
            transition: all 0.3s;
            padding: 0 15px;
            margin: auto;
        }

        .btn:hover {
            opacity: 0.7;
        }


        .links {
            margin: 25px;
            text-align: center;
        }

        .links a {
            text-decoration: none;
            color: #2B547E;
        }

        .links a:hover {
            font-weight: bold;
        }


        /* home page  */

        .nav {
            background-color: #fff;
            display: flex;
            flex-direction: row;
            justify-content: space-around;
            line-height: 60px;
            z-index: 100;
        }

        .logo {
            font-size: 25px;
            font-weight: 900;
        }

        .error {
            color: red;
            text-align: center;
        }

        .girisbasarili {
            color: green;
            text-align: center;
        }

        .logo a {
            text-decoration: none;
            color: #000;
        }

        .error {
            color: #e53e3e;
            margin-bottom: 1rem;
        }

        .right-links a {
            padding: 0 10px;
        }

        main {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-top: 60px;
        }

        .main-box {
            display: flex;
            flex-direction: column;
            width: 70%;
        }

        .main-box .top {
            display: flex;
            flex-direction: row;
            justify-content: space-between;
        }

        .bottom {
            width: 100%;
            margin-top: 20px;
        }

        @media only screen and (max-width: 840px) {
            .main-box .top {
                display: flex;
                flex-direction: column;
                justify-content: space-between;
            }

            .top .box {
                margin: 10px 10px;
            }

            .bottom {
                margin-top: 0;
            }
        }


        .message {
            text-align: left;
            /* background: #f9ede4; */
            padding: 15px 0px;
            /* border: 1px solid #699062; */
            /* border-radius: 5px; */
            /* margin-bottom: 10px; */
            color: rebeccapurple;
        }

        .container form .login {
            width: 100%;
            margin-top: 30px;
            background: #262626;
            color: #fff;
            border: none;
            line-height: 50px;
            font-size: 18px;
            letter-spacing: 0.025em;
            cursor: pointer;
            font-weight: 800;
            border-radius: 8px;
            box-shadow: 0 5px 10px rgba(0, 0, 0, 0.15);
            transition: all 0.3s ease;
        }

        .container form .login:hover {
            background: #5222fd;
        }

        /* Utility Buttons */
        .utility-bar {
            display: flex;
            justify-content: flex-end;
            padding: 10px;
            gap: 10px;
        }

        .utility-btn {
            background: #2B547E;
            color: white;
            border: none;
            padding: 10px 20px;
            font-size: 14px;
            border-radius: 5px;
            cursor: pointer;
        }

        .utility-btn:hover {
            background: #3a5e91;
        }

        /* Dark Mode Styling */
        body.dark-mode {
            background: #121212;
            color: #ffffff;
        }

        body.dark-mode .box {
            background: #1e1e1e;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.5);
        }

        body.dark-mode .utility-btn {
            background: #ffffff;
            color: #2B547E;
        }

        body.dark-mode .links a {
            color: #9bb6d7;
        }

        .form-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 40px;
            font-weight: 600;
            color: #2B547E;
            gap: 20px;
            /* Aralık eklendi */
        }

        .utility-icon {
            font-size: 20px;
            color: #2B547E;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .utility-icon:hover {
            color: #3a5e91;
        }

        @media (max-width: 600px) {
            .form-header {
                font-size: 28px;
            }

            .utility-icon {
                font-size: 18px;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <!-- Dil ve Tema Butonları -->

        <!-- Giriş Formu -->
        <div class="form-box box">
            <!-- Dil Mesajı ve Tema Varsayılanları -->
            <header class="form-header">
                <i id="toggle-language" class="fa fa-flag utility-icon" title="Dil Değiştir"></i>
                <span>Giriş Yap</span>
                <i id="toggle-theme" class="fa fa-moon-o utility-icon" title="Tema Değiştir"></i>
            </header>
            <hr>


            <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                <div class="form-box">
                    <div class="input-container">
                        <i class="fa fa-envelope icon"></i>
                        <input class="input-field" type="email" placeholder="Email" name="email" id="email-placeholder">
                    </div>
                    <div class="input-container">
                        <i class="fa fa-lock icon"></i>
                        <input class="input-field password" type="password" placeholder="Şifreniz" name="password"
                            id="password-placeholder">
                        <i class="fa fa-eye toggle icon"></i>
                    </div>
                </div>
                <input type="submit" name="login" id="submit" value="Giriş Yap" class="login">
                <div class="links">
                    <span><a href="forgot" id="forgot-link">Şifremi unuttum</a></span>
                    <hr>
                    <a href="register" id="register-link">Kayıt Ol</a>
                </div>
            </form>
        </div>
    </div>

    <script>
// Dil değişim metinleri
const texts = {
    en: {
        header: "Login",
        emailPlaceholder: "Email",
        passwordPlaceholder: "Your Password",
        forgot: "Forgot Password",
        register: "Register",
        loginButton: "Login"
    },
    tr: {
        header: "Giriş Yap",
        emailPlaceholder: "Email",
        passwordPlaceholder: "Şifreniz",
        forgot: "Şifremi Unuttum",
        register: "Kayıt Ol",
        loginButton: "Giriş Yap"
    }
};

let currentLang = "tr"; // Varsayılan dil Türkçe

// HTML Elementlerini seçme
const toggleLanguage = document.getElementById("toggle-language");
const toggleTheme = document.getElementById("toggle-theme");
const formHeader = document.querySelector(".form-header span");
const emailPlaceholder = document.querySelector('.input-field[name="email"]');
const passwordPlaceholder = document.querySelector('.input-field[name="password"]');
const forgotLink = document.querySelector('.links span a');
const registerLink = document.getElementById("register-link");
const loginButton = document.querySelector('.login');

// Dil değişimi işlemi
toggleLanguage.addEventListener("click", () => {
    // Dil değiştirme işlemi
    currentLang = currentLang === "tr" ? "en" : "tr";

    // Metin güncellemeleri
    formHeader.textContent = texts[currentLang].header;
    emailPlaceholder.placeholder = texts[currentLang].emailPlaceholder;
    passwordPlaceholder.placeholder = texts[currentLang].passwordPlaceholder;
    forgotLink.textContent = texts[currentLang].forgot;
    registerLink.textContent = texts[currentLang].register;
    loginButton.value = texts[currentLang].loginButton;

    // Bayrak ikonunun değiştirilmesi
    if (currentLang === "en") {
        // İngilizce bayrağına geçiş
        toggleLanguage.classList.remove("fa-flag");
        toggleLanguage.classList.add("fa-flag-usa");
    } else {
        // Türk bayrağına geçiş
        toggleLanguage.classList.remove("fa-flag-usa");
        toggleLanguage.classList.add("fa-flag");
    }
});

// Tema değişimi
toggleTheme.addEventListener("click", () => {
    // Karanlık ve açık mod için body'ye sınıf ekle/kaldır
    document.body.classList.toggle("dark-mode");

    // İkonu değiştir
    toggleTheme.classList.toggle("fa-moon-o");
    toggleTheme.classList.toggle("fa-sun-o");
});

// Sayfa yüklendiğinde varsayılan ayar
document.addEventListener("DOMContentLoaded", () => {
    // Varsayılan dil Türkçe
    formHeader.textContent = texts[currentLang].header;
    emailPlaceholder.placeholder = texts[currentLang].emailPlaceholder;
    passwordPlaceholder.placeholder = texts[currentLang].passwordPlaceholder;
    forgotLink.textContent = texts[currentLang].forgot;
    registerLink.textContent = texts[currentLang].register;
    loginButton.value = texts[currentLang].loginButton;

    // Varsayılan ikon
    toggleLanguage.classList.add("fa-flag"); // Başlangıçta Türk bayrağı
});

// Responsive Design için ek stil
document.addEventListener("DOMContentLoaded", () => {
    const mobileCSS = document.createElement("style");
    mobileCSS.innerHTML = `
        @media (max-width: 600px) {
            .form-box {
                width: 90%;
                padding: 20px;
            }

            .input-container {
                width: 100%;
            }

            .utility-bar {
                justify-content: center;
                gap: 5px;
            }
        }
    `;
    document.head.appendChild(mobileCSS);
});

const toggle = document.querySelector(".toggle"),
      input = document.querySelector(".password");
    toggle.addEventListener("click", () => {
      if (input.type === "password") {
        input.type = "text";
        toggle.classList.replace("fa-eye-slash", "fa-eye");
      } else {
        input.type = "password";
      }
    })

    </script>

</body>

</html>