//PAGE LOADER CONTROL start
window.addEventListener("load", () => {
  const loader = document.getElementById("pageLoader");

  if (!loader) return;

  setTimeout(() => {
    loader.classList.add("hide");
  }, 2500); // ⏱ 2.5 seconds
});
//PAGE LOADER CONTROL end

// sticky header section start
  const header = document.querySelector('.site-header');

  window.addEventListener('scroll', () => {
    if(window.scrollY > 50){
      header.classList.add('sticky');
    } else {
      header.classList.remove('sticky');
    }
  });
// sticky header section end

// menu hamburger section start 
const openMenu = document.getElementById("openMenu");
const closeMenu = document.getElementById("closeMenu");
const sideMenu = document.getElementById("sideMenu");
const overlay = document.getElementById("menuOverlay");

openMenu.addEventListener("click", () => {
  sideMenu.classList.add("active");
  overlay.classList.add("active");
});

closeMenu.addEventListener("click", closeAll);
overlay.addEventListener("click", closeAll);

function closeAll() {
  sideMenu.classList.remove("active");
  overlay.classList.remove("active");
}
// menu hamburger section end

// footer section 
const accordions = document.querySelectorAll(".accordion");

accordions.forEach(acc => {
  const header = acc.querySelector(".accordion-header");

  header.addEventListener("click", () => {
    acc.classList.toggle("active");
  });
});


// sticky section start

document.addEventListener("DOMContentLoaded", () => {

    const navItems = document.querySelectorAll(".floatingNav .listItem");
    let currentActive = navItems[0];
    let isScrolling = false;

    currentActive.classList.add("active");

    /* =========================
       CUSTOM SMOOTH SCROLL
    ========================== */
    function smoothScrollTo(targetY, duration = 900) {
        const startY = window.pageYOffset;
        const distance = targetY - startY;
        let startTime = null;

        function easeInOutQuad(t) {
            return t < 0.5
                ? 2 * t * t
                : 1 - Math.pow(-2 * t + 2, 2) / 2;
        }

        function animation(currentTime) {
            if (startTime === null) startTime = currentTime;
            const timeElapsed = currentTime - startTime;
            const progress = Math.min(timeElapsed / duration, 1);
            const ease = easeInOutQuad(progress);

            window.scrollTo(0, startY + distance * ease);

            if (timeElapsed < duration) {
                requestAnimationFrame(animation);
            } else {
                isScrolling = false;
            }
        }

        isScrolling = true;
        requestAnimationFrame(animation);
    }

    /* =========================
       CLICK EVENTS
    ========================== */
    navItems.forEach(item => {

        const targetId = item.dataset.target;
        const section = document.getElementById(targetId);

        item.addEventListener("click", () => {

            navItems.forEach(i => i.classList.remove("active"));
            item.classList.add("active");
            currentActive = item;

            if (section) {
                const offset = 0; // header offset if needed
                const targetY =
                    section.getBoundingClientRect().top +
                    window.pageYOffset -
                    offset;

                smoothScrollTo(targetY, 1000); // ← SPEED CONTROL
            }
        });

        /* HOVER EFFECT */
        item.addEventListener("mouseenter", () => {
            item.classList.add("active");
        });

        item.addEventListener("mouseleave", () => {
            if (item !== currentActive) {
                item.classList.remove("active");
            }
        });
    });

    /* =========================
       SCROLL → AUTO ACTIVE
    ========================== */
    window.addEventListener("scroll", () => {
        if (isScrolling) return;

        let scrollPos = window.scrollY + window.innerHeight / 2;

        navItems.forEach(item => {
            const section = document.getElementById(item.dataset.target);

            if (section) {
                if (
                    scrollPos >= section.offsetTop &&
                    scrollPos < section.offsetTop + section.offsetHeight
                ) {
                    if (currentActive !== item) {
                        navItems.forEach(i => i.classList.remove("active"));
                        item.classList.add("active");
                        currentActive = item;
                    }
                }
            }
        });
    });

});
// sticky section end