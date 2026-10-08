<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>About Us | CampusDesk</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="css/about.css">

    <!-- Lets the CSS know that JavaScript is available -->
    <script>
        document.documentElement.classList.add("js");
    </script>
</head>

<body>

    <!-- Dotted decorations -->
    <div class="dots dots-left"></div>
    <div class="dots dots-right"></div>

    <!-- Green waves at the bottom -->
    <svg class="waves" viewBox="0 0 1440 320" preserveAspectRatio="none">
        <path d="M0,200 C240,100 480,280 720,210 C960,140 1200,100 1440,180 L1440,320 L0,320 Z"></path>
        <path d="M0,250 C300,170 560,300 840,240 C1100,185 1300,170 1440,230 L1440,320 L0,320 Z"></path>
    </svg>


    <!-- HERO -->
    <section class="hero">
        <div class="container hero-grid">

            <div class="hero-copy">
                <span class="pill"><span class="pill-dot"></span>About CampusDesk</span>

                <h1>Built by students, <span>for students.</span></h1>

                <p>
                    CampusDesk is a student grievance and application management system.
                    Raise an issue, follow it, and see it resolved, all in one place.
                </p>

                <ul class="hero-points">
                    <li><i class="fa-solid fa-lock"></i>Secure</li>
                    <li><i class="fa-solid fa-eye"></i>Transparent</li>
                    <li><i class="fa-solid fa-bolt"></i>Timely</li>
                </ul>
            </div>

            <div class="tilt">

                <div class="status-card">

                    <span class="tick tick-tl"></span>
                    <span class="tick tick-tr"></span>
                    <span class="tick tick-bl"></span>
                    <span class="tick tick-br"></span>

                    <div class="status-top">
                        <strong>Grievance status</strong>
                        <span class="badge">Resolved</span>
                    </div>

                    <ul class="timeline">
                        <li class="done" style="--i: 0;">Submitted by student <small>Day 1</small></li>
                        <li class="done" style="--i: 1;">Reviewed by authority <small>Day 2</small></li>
                        <li class="done" style="--i: 2;">Action taken <small>Day 3</small></li>
                        <li class="done" style="--i: 3;">Marked as resolved <small>Day 4</small></li>
                    </ul>

                </div>

            </div>

        </div>
    </section>


    <!-- SCROLLING BAND -->
    <div class="ticker">
        <div class="ticker-track">

            <div class="ticker-group">
                <span>Grievances</span>
                <span>Suggestions</span>
                <span>Applications</span>
                <span>Notifications</span>
                <span>Attachments</span>
                <span>Reports</span>
                <span>Audit logs</span>
            </div>

            <div class="ticker-group" aria-hidden="true">
                <span>Grievances</span>
                <span>Suggestions</span>
                <span>Applications</span>
                <span>Notifications</span>
                <span>Attachments</span>
                <span>Reports</span>
                <span>Audit logs</span>
            </div>

        </div>
    </div>


    <!-- PROJECT BENTO -->
    <section class="section">
        <div class="container">

            <div class="section-head">
                <p class="eyebrow">The project</p>
                <h2>Everything in one portal</h2>
            </div>

            <div class="bento">

                <div class="tile tile-dark">

                    <svg class="tile-rings" viewBox="0 0 200 200" aria-hidden="true">
                        <circle cx="100" cy="100" r="95" fill="none" stroke="#C8F135" stroke-width="1.5" opacity="0.3"></circle>
                        <circle cx="100" cy="100" r="68" fill="none" stroke="#C8F135" stroke-width="1.5" opacity="0.5"></circle>
                        <circle cx="100" cy="100" r="38" fill="#C8F135"></circle>
                    </svg>

                    <div>
                        <h3>One system for grievances, suggestions and applications.</h3>
                        <p>
                            CampusDesk replaces paper complaints and scattered messages with a
                            transparent portal. Every submission is tracked and every action is recorded.
                        </p>
                    </div>

                    <div class="meta">
                        <span>Student Grievance Management System</span>
                        <span>Web application</span>
                    </div>
                </div>

                <div class="tile tile-accent">
                    <div class="big-number" data-count="3">3</div>
                    <p>User roles: Student, Authority and Admin.</p>
                </div>

                <div class="tile">
                    <div class="big-number">24/7</div>
                    <p>Submit anytime and track live status.</p>
                </div>

                <div class="tile span-2">
                    <h3>Tech stack</h3>
                    <p>What the project is built with.</p>
                    <div class="chip-row">
                        <span class="chip">HTML5</span>
                        <span class="chip">CSS3</span>
                        <span class="chip">JavaScript</span>
                        <span class="chip">PHP</span>
                        <span class="chip">PostgreSQL</span>
                    </div>
                </div>

                <div class="tile span-2">
                    <h3>Who uses it</h3>

                    <ul class="mini-list">
                        <li><i class="fa-solid fa-user-graduate"></i>Student <span>Submit and track</span></li>
                        <li><i class="fa-solid fa-user-tie"></i>Authority <span>Review and resolve</span></li>
                        <li><i class="fa-solid fa-user-shield"></i>Admin <span>Manage and monitor</span></li>
                    </ul>
                </div>

                <div class="tile span-2">
                    <h3>Key features</h3>
                    <div class="chip-row">
                        <span class="chip">Grievances</span>
                        <span class="chip">Suggestions</span>
                        <span class="chip">Applications</span>
                        <span class="chip">Notifications</span>
                        <span class="chip">Attachments</span>
                        <span class="chip">Reports</span>
                        <span class="chip">Audit logs</span>
                    </div>
                </div>

            </div>

        </div>
    </section>


    <!-- TEAM -->
    <section class="section">
        <div class="container">

            <div class="section-head">
                <p class="eyebrow">The team</p>
                <h2>The three people behind CampusDesk</h2>
            </div>

            <div class="team-grid">

                <div class="member">
                    <div class="member-top"><span>OG</span></div>
                    <div class="member-body">
                        <h3>Omkar Gavane</h3>
                        <p class="member-role">Your role here</p>
                        <p>Write one or two lines about what Omkar worked on in this project.</p>
                        <div class="socials">
                            <a href="#"><i class="fa-brands fa-github"></i></a>
                            <a href="#"><i class="fa-brands fa-linkedin-in"></i></a>
                            <a href="#"><i class="fa-solid fa-envelope"></i></a>
                        </div>
                    </div>
                </div>

                <div class="member">
                    <div class="member-top"><span>AN</span></div>
                    <div class="member-body">
                        <h3>Amod Naikare</h3>
                        <p class="member-role">UI and Design</p>
                        <p>Designed the look and feel of CampusDesk, from the home page to the dashboards.</p>
                        <div class="socials">
                            <a href="#"><i class="fa-brands fa-github"></i></a>
                            <a href="#"><i class="fa-brands fa-linkedin-in"></i></a>
                            <a href="#"><i class="fa-solid fa-envelope"></i></a>
                        </div>
                    </div>
                </div>

                <div class="member">
                    <div class="member-top"><span>GM</span></div>
                    <div class="member-body">
                        <h3>Ganesh Mane</h3>
                        <p class="member-role">Your role here</p>
                        <p>Write one or two lines about what Ganesh worked on in this project.</p>
                        <div class="socials">
                            <a href="#"><i class="fa-brands fa-github"></i></a>
                            <a href="#"><i class="fa-brands fa-linkedin-in"></i></a>
                            <a href="#"><i class="fa-solid fa-envelope"></i></a>
                        </div>
                    </div>
                </div>

            </div>

        </div>
    </section>


    <!-- CALL TO ACTION (delete this whole section if you do not want it) -->
    <section class="section cta-section">
        <div class="container">

            <div class="cta">

                <svg class="tile-rings" viewBox="0 0 200 200" aria-hidden="true">
                    <circle cx="100" cy="100" r="95" fill="none" stroke="#C8F135" stroke-width="1.5" opacity="0.3"></circle>
                    <circle cx="100" cy="100" r="68" fill="none" stroke="#C8F135" stroke-width="1.5" opacity="0.5"></circle>
                    <circle cx="100" cy="100" r="38" fill="#C8F135"></circle>
                </svg>

                <div class="cta-text">
                    <h2>Ready to raise your voice?</h2>
                    <p>Log in or register and follow every step until it is resolved.</p>
                </div>

                <a href="index.php" class="cta-btn">
                    Open CampusDesk <i class="fa-solid fa-arrow-right"></i>
                </a>

            </div>

        </div>
    </section>


    <footer class="site-footer">
        <div class="container footer-inner">
            <span><strong>CampusDesk</strong> · Student Grievance Management System</span>
            <span>© 2026 CampusDesk. All rights reserved.</span>
        </div>
    </footer>


    <script>
        (function() {

            var reduce = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
            var canHover = window.matchMedia("(hover: hover)").matches;

            /* ---------- Soft glow that follows the mouse on cards ---------- */
            var cards = document.querySelectorAll(".tile, .member");

            cards.forEach(function(card) {
                card.addEventListener("mousemove", function(event) {
                    var box = card.getBoundingClientRect();
                    card.style.setProperty("--mx", (event.clientX - box.left) + "px");
                    card.style.setProperty("--my", (event.clientY - box.top) + "px");
                });
            });

            /* ---------- Gentle 3D tilt on the status card ---------- */
            var tilt = document.querySelector(".tilt");

            if (tilt && !reduce && canHover) {

                tilt.addEventListener("mousemove", function(event) {

                    var box = tilt.getBoundingClientRect();
                    var x = (event.clientX - box.left) / box.width - 0.5;
                    var y = (event.clientY - box.top) / box.height - 0.5;

                    tilt.style.transform =
                        "perspective(900px) rotateX(" + (-y * 8) + "deg) rotateY(" + (x * 8) + "deg)";
                });

                tilt.addEventListener("mouseleave", function() {
                    tilt.style.transform = "";
                });
            }

            /* ---------- Count up number ---------- */
            function countUp(element) {

                var target = parseInt(element.getAttribute("data-count"), 10);
                var start = null;

                function step(time) {

                    if (start === null) {
                        start = time;
                    }

                    var progress = Math.min((time - start) / 900, 1);

                    element.textContent = Math.round(target * progress);

                    if (progress < 1) {
                        requestAnimationFrame(step);
                    }
                }

                requestAnimationFrame(step);
            }

            /* ---------- Fade in cards while scrolling ---------- */
            if (reduce || !("IntersectionObserver" in window)) {
                return;
            }

            var items = document.querySelectorAll(".section-head, .tile, .member, .cta");

            var observer = new IntersectionObserver(function(entries) {

                entries.forEach(function(entry) {

                    if (!entry.isIntersecting) {
                        return;
                    }

                    var item = entry.target;

                    item.classList.add("in");
                    observer.unobserve(item);

                    var number = item.querySelector("[data-count]");

                    if (number) {
                        countUp(number);
                    }

                    // Remove the helper classes after the animation is finished
                    setTimeout(function() {
                        item.classList.remove("reveal", "in");
                    }, 1500);
                });

            }, {
                threshold: 0.15
            });

            items.forEach(function(item) {

                var position = Array.prototype.indexOf.call(item.parentNode.children, item);

                item.style.setProperty("--d", ((position % 4) * 90) + "ms");
                item.classList.add("reveal");

                observer.observe(item);
            });

        })();
    </script>

</body>

</html>
