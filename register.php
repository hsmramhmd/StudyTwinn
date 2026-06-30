<?php
// 1. DATABASE CONFIGURATION & INITIALIZATION
$host = "localhost";
$user = "root";
$pass = "";
$db_name = "studytwin";

$conn = new mysqli($host, $user, $pass, $db_name);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

session_start();
$error = "";
$success = "";
$initial_step = 1; // Controls the onboarding text slide step

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $initial_step = 3; // Lock user to the registration sheet context if form validation runs
    
    // Sanitize user inputs
    $full_name = trim($_POST['full_name']);
    // Automatically force the student's name into CAPITAL LETTERS
    $full_name = strtoupper($conn->real_escape_string($full_name));
    
    $student_number = trim($_POST['student_number']);
    $student_number = $conn->real_escape_string($student_number);
    
    // Formulate a dynamic matching system email based on the standard domain rule provided in your login screen
    $email = $student_number . "@student.uitm.edu.my";
    
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role = "student"; // lowercase to match schema & login

    // 2. INPUT VALIDATION PLATFORM
    if ($password !== $confirm_password) {
        $error = "Passwords do not match! Please verify your inputs.";
    } else {
        // Query database context to evaluate if student metadata records exist already
        $check_query = "SELECT * FROM USERS WHERE email = '$email'";
        $check_result = $conn->query($check_query);

        if ($check_result && $check_result->num_rows > 0) {
            $error = "This student number is already registered under studytwin.";
        } else {
            // 3. DATABASE EXECUTION PIPELINE 
            // Matching your schema data map definitions from login verification patterns
            $insert_query = "INSERT INTO USERS (full_name, email, password, role) VALUES ('$full_name', '$email', '$password', '$role')";
            
            if ($conn->query($insert_query)) {
                $success = "Registration successful! Redirecting to login portal...";
                header("refresh:2; url=index.php"); // Redirects to your login page (assumed index.php/login.php)
            } else {
                $error = "Registration failed: " . $conn->error;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>studytwin - Create Account</title>
    <style>
        /* CSS RESET & REBRANDED DESIGN VARIABLES */
        :root {
            --studytwin-teal: #116979;
            --studytwin-teal-dark: #0b4e5a;
            --studytwin-orange: #f0672b;
            --canvas-bg: #f4f8f9;
            --text-dark: #20363a;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
        }

        body {
            background-color: var(--canvas-bg);
            display: flex;
            min-height: 100vh;
            color: var(--text-dark);
            overflow-x: hidden;
        }

        /* DESKTOP SPLIT CONTAINER */
        .desktop-container {
            display: flex;
            width: 100%;
        }

        /* LEFT SIDE BRANDING HERO PANEL */
        .brand-panel {
            flex: 1.2;
            background: linear-gradient(135deg, var(--studytwin-teal) 0%, var(--studytwin-teal-dark) 100%);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 60px;
            position: relative;
            box-shadow: 10px 0 30px rgba(11, 78, 90, 0.15);
        }

        /* RIGHT SIDE INTERACTIVE FORM PANEL */
        .form-panel {
            flex: 1;
            background-color: #ffffff;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 60px 10% 40px 10%;
            position: relative;
        }

        /* MASCOT BRAND LOGO GRAPHIC SLOTS */
        .mascot-art-frame {
            width: 320px;
            height: 320px;
            margin-bottom: 30px;
            filter: drop-shadow(0 20px 30px rgba(0, 0, 0, 0.15));
        }

        .brand-panel h1 {
            color: #ffffff;
            font-size: 42px;
            font-weight: 800;
            letter-spacing: -0.5px;
            margin-bottom: 8px;
        }

        .system-branding-label {
            font-size: 14px;
            font-weight: 700;
            letter-spacing: 2px;
            text-transform: uppercase;
            color: #e2f1f3;
            opacity: 0.9;
        }

        /* MULTI-STEP CONTEXT VIEW CARDS */
        .step-view-card {
            display: none;
            flex-direction: column;
            width: 100%;
            max-width: 440px;
            margin: 0 auto;
            animation: fadeIn 0.4s ease forwards;
        }

        .step-view-card.active-view {
            display: flex;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .back-navigation-btn {
            align-self: flex-start;
            background: none;
            border: none;
            color: var(--studytwin-teal);
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 30px;
            transition: color 0.2s ease;
        }

        .back-navigation-btn:hover {
            color: var(--studytwin-orange);
        }

        /* FORMS & HEADLINES TYPOGRAPHY */
        .headline-title {
            font-size: 32px;
            font-weight: 800;
            color: var(--text-dark);
            line-height: 1.2;
            margin-bottom: 16px;
        }

        .body-description {
            font-size: 16px;
            color: #556b6e;
            line-height: 1.6;
            margin-bottom: 40px;
        }

        .form-helper-text {
            font-size: 14px;
            color: #637a7d;
            margin-bottom: 25px;
        }

        /* FOOTER PROMPT LINK SETS */
        .registration-footer-prompt {
            text-align: center;
            font-size: 14px;
            margin-top: 20px;
            color: #637a7d;
        }

        .registration-footer-prompt a {
            color: var(--studytwin-orange);
            text-decoration: none;
            font-weight: 700;
            transition: color 0.2s;
        }

        .registration-footer-prompt a:hover {
            text-decoration: underline;
        }

        /* INPUT FIELD ARCHITECTURES */
        .input-group {
            margin-bottom: 18px;
        }

        .input-group label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #485f62;
            margin-bottom: 6px;
        }

        .input-group input {
            width: 100%;
            padding: 13px 16px;
            border: 1px solid #cedfe1;
            border-radius: 12px;
            font-size: 15px;
            background-color: #f7fafb;
            outline: none;
            transition: all 0.2s ease;
        }

        .input-group input:focus {
            border-color: var(--studytwin-teal);
            background-color: #ffffff;
            box-shadow: 0 0 0 4px rgba(17, 105, 121, 0.1);
        }

        .error-banner {
            background-color: #fff0f1;
            border-left: 4px solid #d43f3f;
            color: #ac2b2b;
            padding: 14px;
            font-size: 14px;
            border-radius: 6px;
            margin-bottom: 25px;
            font-weight: 500;
        }

        .success-banner {
            background-color: #e6f9f0;
            border-left: 4px solid #1fa76a;
            color: #136a43;
            padding: 14px;
            font-size: 14px;
            border-radius: 6px;
            margin-bottom: 25px;
            font-weight: 500;
        }

        /* BUTTON CONTROLS */
        .action-button {
            width: 100%;
            padding: 16px;
            background-color: var(--studytwin-teal);
            color: #ffffff;
            border: none;
            border-radius: 14px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s ease;
            text-align: center;
            text-decoration: none;
        }

        .action-button:hover {
            opacity: 0.95;
            transform: translateY(-1px);
        }

        .action-button.orange-accent {
            background-color: var(--studytwin-orange);
            box-shadow: 0 8px 20px rgba(240, 103, 43, 0.25);
        }

        .privacy-link-text {
            font-size: 12px;
            color: #9cb1b4;
            margin-top: 30px;
            text-align: center;
        }

        /* RESPONSIVE LAYOUT SYSTEM FOR SMALLER MONITORS */
        @media (max-width: 900px) {
            .desktop-container {
                flex-direction: column;
            }
            .brand-panel {
                padding: 40px 20px;
            }
            .mascot-art-frame {
                width: 200px;
                height: 200px;
            }
            .form-panel {
                padding: 40px 40px 60px 40px;
            }
        }
    </style>
<?php include_once("includes/theme.php"); inject_theme_styles_and_script(); ?>
</head>
<body>

    <div class="desktop-container">

        <div class="brand-panel">
            <div class="mascot-art-frame">
                <svg viewBox="0 0 200 200" width="100%" height="100%">
                    <circle cx="100" cy="100" r="88" fill="#ffffff" stroke="#0b4e5a" stroke-width="4.5"/>
                    <circle cx="100" cy="100" r="79" fill="none" stroke="#f0672b" stroke-width="1.5" stroke-dasharray="5 4"/>
                    <path d="M 45,65 Q 50,55 58,60 M 145,65 Q 150,55 142,58" stroke="#bcd4d7" stroke-width="2" fill="none"/>
                    <rect x="42" y="82" width="24" height="22" rx="3" fill="#eef7f9" stroke="#bcd4d7" stroke-width="1.5"/>
                    <line x1="42" y1="90" x2="66" y2="90" stroke="#bcd4d7" stroke-width="1.5"/>
                    <circle cx="49" cy="97" r="1.5" fill="#f0672b"/> <circle cx="56" cy="97" r="1.5" fill="#116979"/>
                    <path d="M 125,120 Q 165,100 152,145 Q 120,155 125,120 Z" fill="#f0672b"/>
                    <path d="M 142,108 Q 155,120 152,145 Z" fill="#ffffff"/>
                    <polygon points="65,75 50,35 85,55" fill="#f0672b"/>
                    <polygon points="62,71 54,42 76,56" fill="#f4ebd9"/>
                    <polygon points="135,75 150,35 115,55" fill="#f0672b"/>
                    <polygon points="138,71 146,42 124,56" fill="#f4ebd9"/>
                    <path d="M 55,90 Q 100,135 145,90 Q 155,120 100,135 Q 45,120 55,90 Z" fill="#f0672b"/>
                    <ellipse cx="100" cy="92" rx="44" ry="34" fill="#f0672b"/>
                    <path d="M 56,95 Q 75,120 100,112 Q 125,120 144,95 Q 150,112 100,126 Q 50,112 56,95 Z" fill="#ffffff"/>
                    <circle cx="80" cy="90" r="14" stroke="#20363a" stroke-width="3" fill="none"/>
                    <circle cx="120" cy="90" r="14" stroke="#20363a" stroke-width="3" fill="none"/>
                    <line x1="94" y1="90" x2="106" y2="90" stroke="#20363a" stroke-width="3"/>
                    <circle cx="80" cy="90" r="4" fill="#20363a"/>
                    <circle cx="120" cy="90" r="4" fill="#20363a"/>
                    <ellipse cx="100" cy="105" rx="7.5" ry="5" fill="#20363a"/>
                    <path d="M 96,112 Q 100,116 104,112" stroke="#20363a" stroke-width="2" fill="none"/>
                    <polygon points="84,134 84,148 100,141" fill="#116979"/>
                    <polygon points="116,134 116,148 100,141" fill="#116979"/>
                    <circle cx="100" cy="141" r="4" fill="#116979"/>
                </svg>
            </div>
            <h1>studytwin</h1>
            <div class="system-branding-label">Tutor Booking System</div>
        </div>

        <div class="form-panel">
            <div style="position:absolute; top:20px; right:20px; z-index:10;">
                <?php render_theme_toggle(); ?>
            </div>

            <div class="step-view-card <?php echo ($initial_step == 1) ? 'active-view' : ''; ?>" id="screen-step-1">
                <h2 class="headline-title">Join the studytwin Network Today</h2>
                <p class="body-description">
                    Register to open a shared automated academic workspace. Handle peer scheduling components, synchronize tasks, and construct custom milestones alongside university peers.
                </p>
                <button class="action-button orange-accent" onclick="navigateToCard(2)">Create Account</button>
                <div class="registration-footer-prompt">
                    Already have an account? <a href="index.php">Log in here</a>
                </div>
            </div>

            <div class="step-view-card <?php echo ($initial_step == 2) ? 'active-view' : ''; ?>" id="screen-step-2">
                <button class="back-navigation-btn" onclick="navigateToCard(1)">❮ Back</button>
                <h2 class="headline-title">Register in standard steps.</h2>
                <p class="body-description">
                    Provide verified student configurations to build safe community interactions. Check out scheduling sheets, find learning matches, and study together smoothly.
                </p>
                <button class="action-button" onclick="navigateToCard(3)">Proceed to Register</button>
            </div>

            <div class="step-view-card <?php echo ($initial_step == 3) ? 'active-view' : ''; ?>" id="screen-step-3">
                <button class="back-navigation-btn" onclick="navigateToCard(2)">❮ Back</button>
                
                <h2 class="headline-title">Create your account</h2>
                <div class="form-helper-text">Enter your credentials to setup your workspace connection profile.</div>

                <?php if (!empty($error)): ?>
                    <div class="error-banner">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($success)): ?>
                    <div class="success-banner">
                        <?php echo htmlspecialchars($success); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                    <div class="input-group">
                        <label>Full Name (Will automatically capitalize)</label>
                        <input type="text" name="full_name" placeholder="MUHAMMAD BIN ABDUL" style="text-transform: uppercase;" required 
                               value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>">
                    </div>

                    <div class="input-group">
                        <label>Student Number</label>
                        <input type="text" name="student_number" placeholder="2024XXXXXXXX" required 
                               value="<?php echo isset($_POST['student_number']) ? htmlspecialchars($_POST['student_number']) : ''; ?>">
                    </div>

                    <div class="input-group">
                        <label>Password</label>
                        <input type="password" name="password" placeholder="••••••••" required>
                    </div>

                    <div class="input-group">
                        <label>Confirm Password</label>
                        <input type="password" name="confirm_password" placeholder="••••••••" required>
                    </div>

                    <button type="submit" class="action-button">Register Account</button>
                    
                    <div class="registration-footer-prompt">
                        Already have an account? <a href="index.php">Log in here</a>
                    </div>
                </form>

                <div class="privacy-link-text">
                    For more information, see our <a href="#">Privacy Policy</a>.
                </div>
            </div>

         </div>

    </div>

    <script>
        function navigateToCard(stepNumber) {
            document.querySelectorAll('.step-view-card').forEach(function(card) {
                card.classList.remove('active-view');
            });
            
            const targetId = 'screen-step-' + stepNumber;
            const targetCard = document.getElementById(targetId);
            if (targetCard) {
                targetCard.classList.add('active-view');
            }
        }
    </script>

</body>
</html>