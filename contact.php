<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Contact | CampusDesk</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="css/contact.css">
</head>

<body>
    <svg width="0" height="0" style="position:absolute">
        <symbol id="mail" viewBox="0 0 24 24">
            <rect x="3" y="5" width="18" height="14" rx="3" />
            <path d="m4 7 8 6 8-6" />
        </symbol>
        <symbol id="phone" viewBox="0 0 24 24">
            <path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2z" />
        </symbol>
        <symbol id="pin" viewBox="0 0 24 24">
            <path d="M12 21s7-6 7-11a7 7 0 0 0-14 0c0 5 7 11 7 11z" />
            <circle cx="12" cy="10" r="2.5" />
        </symbol>
        <symbol id="clock" viewBox="0 0 24 24">
            <circle cx="12" cy="12" r="9" />
            <path d="M12 7v5l3 2" />
        </symbol>
        <symbol id="arr" viewBox="0 0 24 24">
            <path d="M7 17 17 7M8 7h9v9" />
        </symbol>
        <symbol id="chk" viewBox="0 0 24 24">
            <path d="m5 12 5 5 9-10" />
        </symbol>
        <symbol id="users" viewBox="0 0 24 24">
            <circle cx="9" cy="8" r="3.5" />
            <path d="M2.5 20a6.5 6.5 0 0 1 13 0M16 4.5a3.5 3.5 0 0 1 0 7M18 14a6 6 0 0 1 3.5 6" />
        </symbol>
        <symbol id="shield" viewBox="0 0 24 24">
            <path d="M12 3 4 6v6c0 5 3.5 8 8 9 4.5-1 8-4 8-9V6z" />
        </symbol>
        <symbol id="chat" viewBox="0 0 24 24">
            <path d="M4 5h16v11H9l-5 4z" />
        </symbol>
        <symbol id="copy" viewBox="0 0 24 24">
            <rect x="8" y="8" width="12" height="12" rx="2" />
            <path d="M16 8V6a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h2" />
        </symbol>
        <symbol id="nav" viewBox="0 0 24 24">
            <path d="m3 11 18-8-8 18-2-8z" />
        </symbol>
        <symbol id="cap" viewBox="0 0 24 24">
            <path d="m2 9 10-5 10 5-10 5zM6 11.5V16c3 2.5 9 2.5 12 0v-4.5" />
        </symbol>
    </svg>

    <div class="dots" style="left:20px;top:420px"></div>
    <div class="dots" style="right:20px;top:120px"></div>

    <section class="hero">
        <div class="ghost">CONTACT</div>
        <div class="wrap grid">
            <div>
                <span class="badge"><b></b>Support team online</span>
                <h1>Let's <span>talk.</span></h1>
                <p class="lead">Skip the forms. Pick whichever way suits you and a real person from the CampusDesk team
                    will answer.</p>
                <div class="reply">
                    <div class="ck"><svg class="i">
                            <use href="#chk" />
                        </svg></div>
                    <div><small>Average reply time</small><strong>Under 24 hours, Mon to Sat</strong></div>
                </div>
                <div class="team">
                    <div class="av"><i>AS</i><i>RK</i><i>MP</i><i>VD</i></div>The team is online right now
                </div>
            </div>

            <div class="card">
                <div class="row pref">
                    <div class="ico" style="color:var(--ink)"><svg class="i">
                            <use href="#mail" />
                        </svg></div>
                    <div class="txt"><small>Email us <span
                                class="tag">PREFERRED</span></small><strong>support@campusdesk.com</strong></div>
                    <button class="copy" data-copy="support@campusdesk.com"><svg class="i"
                            style="width:14px;height:14px">
                            <use href="#copy" />
                        </svg>Copy</button>
                    <a class="go" href="mailto:support@campusdesk.com"><svg class="i">
                            <use href="#arr" />
                        </svg></a>
                </div>
                <a class="row" href="tel:+910000000000">
                    <div class="ico"><svg class="i">
                            <use href="#phone" />
                        </svg></div>
                    <div class="txt"><small>Call us</small><strong>+91 00000 00000</strong></div>
                    <span class="go"><svg class="i" style="width:14px;height:14px">
                            <use href="#arr" />
                        </svg></span>
                </a>
                <a class="row" href="#map">
                    <div class="ico"><svg class="i">
                            <use href="#pin" />
                        </svg></div>
                    <div class="txt"><small>Visit the help desk</small><strong>Admin Building, Ground Floor</strong>
                    </div>
                    <span class="go"><svg class="i" style="width:14px;height:14px">
                            <use href="#arr" />
                        </svg></span>
                </a>
                <div class="row">
                    <div class="ico"><svg class="i">
                            <use href="#clock" />
                        </svg></div>
                    <div class="txt"><small>Working hours</small><strong>Mon to Sat, 9:00 AM to 5:00 PM</strong></div>
                    <span class="go"><svg class="i" style="width:14px;height:14px">
                            <use href="#arr" />
                        </svg></span>
                </div>
            </div>
        </div>
    </section>

    <div class="band">
        <div class="track" id="track">
            <span>Student support</span><span>Authorities</span><span>Technical
                help</span><span>Feedback</span><span>Grievances</span><span>Applications</span>
        </div>
    </div>

    <section class="sec" id="map">
        <div class="wrap">
            <div class="map">
                <div class="rd"></div>
                <div class="ring r2"></div>
                <div class="ring r1"></div>
                <div class="pin"><svg class="i">
                        <use href="#pin" />
                    </svg></div>
                <div class="info">
                    <div class="ico" style="color:var(--ink)"><svg class="i">
                            <use href="#pin" />
                        </svg></div>
                    <h3>CampusDesk Help Desk</h3>
                    <p>Admin Building, Ground Floor<br>Fergusson College, Pune, 411004</p>
                    <div class="open"><svg class="i" style="width:14px;height:14px">
                            <use href="#clock" />
                        </svg>Open today, 9:00 AM to 5:00 PM</div>
                    <!-- <div class="btns">
                        <a class="btn p" href="#"><svg class="i" style="width:14px;height:14px">
                                <use href="#nav" />
                            </svg>Directions</a>
                        <button class="btn s" data-copy="Admin Building, Ground Floor"><svg class="i"
                                style="width:14px;height:14px">
                                <use href="#copy" />
                            </svg>Copy address</button>
                    </div> -->
                </div>
            </div>

            <div class="three">
                <div class="aud"><span class="go"><svg class="i" style="width:14px;height:14px">
                            <use href="#arr" />
                        </svg></span>
                    <div class="ico"><svg class="i">
                            <use href="#users" />
                        </svg></div><span class="n">01</span>
                    <h3>Students</h3>
                    <p>Login trouble, grievance status or account help. Write to the support email.</p>
                </div>
                <div class="aud dk"><span class="go"
                        style="background:var(--lime);color:var(--ink);border-color:var(--lime)"><svg class="i"
                            style="width:14px;height:14px">
                            <use href="#arr" />
                        </svg></span>
                    <div class="ico" style="color:var(--ink)"><svg class="i">
                            <use href="#shield" />
                        </svg></div><span class="n">02</span>
                    <h3>Authorities</h3>
                    <p>Need access or have a question about reviewing submissions? Reach out by email.</p>
                </div>
                <div class="aud"><span class="go"><svg class="i" style="width:14px;height:14px">
                            <use href="#arr" />
                        </svg></span>
                    <div class="ico"><svg class="i">
                            <use href="#chat" />
                        </svg></div><span class="n">03</span>
                    <h3>Everyone else</h3>
                    <p>Feedback, ideas or press? Send a note and we will point it to the right person.</p>
                </div>
            </div>

            <div class="cta">
                <div>
                    <h2>Prefer to raise it <em>inside the portal?</em></h2>
                    <p>Log in as a student and track every update in one place.</p>
                </div>
                <div class="btns"><a class="btn p" href="login.php">Go to login →</a><a class="btn s"
                        href="about.php">About CampusDesk</a></div>
            </div>
        </div>
    </section>

    <footer>
        <div class="wrap">
            <div class="fg">
                <div>
                    <div class="logo"><b><svg class="i">
                                <use href="#cap" />
                            </svg></b>CampusDesk</div>
                    <p>A student grievance and application management system, built by students for students.</p>
                </div>
                <div>
                    <h4>Pages</h4>
                    <ul>
                        <li><a href="index.php">Home</a></li>
                        <li><a href="about.php">About</a></li>
                        <li><a href="contact.php">Contact</a></li>
                    </ul>
                </div>
                <!--<div>
                    <h4>Portal</h4>
                    <ul>
                        <li><a href="login.php">Student login</a></li>
                        <li><a href="#">Authority login</a></li>
                        <li><a href="#">Register</a></li>
                    </ul>
                </div> -->
                <div>
                    <h4>Contact</h4>
                    <ul>
                        <li>support@campusdesk.com</li>
                        <li>+91 00000 00000</li>
                    </ul>
                </div>
            </div>
            <div class="copyr"><span>© 2026 CampusDesk. All rights reserved.</span><span>Student Grievance Management
                    System</span></div>
        </div>
    </footer>

    <div class="toast" id="toast">Copied to clipboard</div>

    <script src="js/contact.js"></script>
</body>

</html>
