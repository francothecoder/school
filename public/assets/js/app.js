(function(){
  const themeKey = 'moomba_theme';
  const html = document.documentElement;
  const savedTheme = localStorage.getItem(themeKey);
  if (savedTheme) {
    html.setAttribute('data-theme', savedTheme);
  }

  const themeBtn = document.getElementById('themeToggle');
  if (themeBtn) {
    themeBtn.addEventListener('click', function(){
      const next = html.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
      html.setAttribute('data-theme', next);
      localStorage.setItem(themeKey, next);
    });
  }

  const sidebar = document.getElementById('appSidebar');
  const sidebarToggle = document.getElementById('sidebarToggle');
  const sidebarClose = document.getElementById('sidebarClose');
  const sidebarBackdrop = document.getElementById('mobileSidebarBackdrop');
  const closeSidebar = () => {
    if (!sidebar) return;
    sidebar.classList.remove('show');
    sidebarBackdrop?.classList.remove('show');
    document.body.classList.remove('sidebar-open');
  };
  const openSidebar = () => {
    if (!sidebar) return;
    sidebar.classList.add('show');
    sidebarBackdrop?.classList.add('show');
    document.body.classList.add('sidebar-open');
  };
  sidebarToggle?.addEventListener('click', openSidebar);
  sidebarClose?.addEventListener('click', closeSidebar);
  sidebarBackdrop?.addEventListener('click', closeSidebar);
  window.addEventListener('resize', function(){
    if (window.innerWidth >= 1200) closeSidebar();
  });
  document.querySelectorAll('.sidebar .nav-link').forEach(link => {
    link.addEventListener('click', function(){
      if (window.innerWidth < 1200) closeSidebar();
    });
  });

  const classSelect = document.querySelector('[data-role="class-subject-driver"]');
  if (classSelect) {
    const target = document.querySelector(classSelect.dataset.subjectTarget);
    const yearInput = document.querySelector(classSelect.dataset.yearTarget || '');
    const subjectValue = classSelect.dataset.selectedSubject || '';
    const endpoint = classSelect.dataset.endpoint || '';
    const loadSubjects = () => {
      if (!target || !endpoint) return;
      const classId = classSelect.value;
      target.innerHTML = '<option value="">Loading...</option>';
      if (!classId) {
        target.innerHTML = '<option value="">Select subject</option>';
        return;
      }
      const year = yearInput ? yearInput.value : '';
      fetch(endpoint + '?class_id=' + encodeURIComponent(classId) + '&year=' + encodeURIComponent(year))
        .then(r => r.json())
        .then(data => {
          const items = data.subjects || [];
          target.innerHTML = '<option value="">Select subject</option>';
          items.forEach(item => {
            const option = document.createElement('option');
            option.value = item.subject_id;
            option.textContent = item.name + (item.teacher_name ? ' · ' + item.teacher_name : '');
            if (String(item.subject_id) === String(target.dataset.selectedValue || subjectValue)) {
              option.selected = true;
            }
            target.appendChild(option);
          });
        })
        .catch(() => {
          target.innerHTML = '<option value="">Unable to load subjects</option>';
        });
    };
    classSelect.addEventListener('change', loadSubjects);
    if (yearInput) yearInput.addEventListener('change', loadSubjects);
    if (classSelect.value) loadSubjects();
  }

  const sectionDrivers = document.querySelectorAll('[data-role="class-section-driver"]');
  sectionDrivers.forEach(driver => {
    const target = document.querySelector(driver.dataset.sectionTarget);
    const endpoint = driver.dataset.endpoint;
    const loadSections = () => {
      if (!target || !endpoint) return;
      if (!driver.value) {
        target.innerHTML = '<option value="">Select section</option>';
        return;
      }
      fetch(endpoint + '?class_id=' + encodeURIComponent(driver.value))
        .then(r => r.json())
        .then(data => {
          const items = data.sections || [];
          const selected = target.dataset.selectedValue || '';
          target.innerHTML = '<option value="">Select section</option>';
          items.forEach(item => {
            const option = document.createElement('option');
            option.value = item.section_id;
            option.textContent = item.name;
            if (String(item.section_id) === String(selected)) option.selected = true;
            target.appendChild(option);
          });
        })
        .catch(() => {
          target.innerHTML = '<option value="">Unable to load sections</option>';
        });
    };
    driver.addEventListener('change', loadSections);
    if (driver.value) loadSections();
  });


  const loader = document.getElementById('appLoader');
  const loaderText = document.getElementById('appLoaderText');
  const showLoader = (message = 'Processing...') => {
    if (!loader) return;
    if (loaderText) loaderText.textContent = message;
    loader.hidden = false;
    loader.style.display = 'flex';
    loader.classList.add('show');
    loader.setAttribute('aria-hidden', 'false');
  };
  const hideLoader = () => {
    if (!loader) return;
    loader.classList.remove('show');
    loader.setAttribute('aria-hidden', 'true');
    window.setTimeout(() => {
      loader.style.display = 'none';
      loader.hidden = true;
    }, 120);
  };

  hideLoader();

  window.addEventListener('pageshow', hideLoader);
  window.addEventListener('load', function(){
    window.setTimeout(hideLoader, 120);
    document.querySelectorAll('.auto-dismiss-alert').forEach(alert => {
      window.setTimeout(() => {
        alert.style.transition = 'opacity .25s ease, transform .25s ease';
        alert.style.opacity = '0';
        alert.style.transform = 'translateY(-4px)';
        window.setTimeout(() => alert.remove(), 260);
      }, 1800);
    });
  });

  document.querySelectorAll('form').forEach(form => {
    form.addEventListener('submit', function(){
      const submitter = document.activeElement;
      const message = submitter?.dataset?.loaderText || form.dataset.loaderText || 'Processing...';
      showLoader(message);
    });
  });

  document.querySelectorAll('[data-loader-click]').forEach(btn => {
    btn.addEventListener('click', function(){
      showLoader(btn.dataset.loaderClick || 'Loading...');
    });
  });

  const tourConfig = window.__APP_TOUR || {};
  const buildTourSteps = (role) => {
    const steps = [
      {
        popover: {
          title: `Welcome, ${tourConfig.userName || 'User'}`,
          description: 'This quick guided tour highlights the most important areas of LearnTrack Pro. You can start it again anytime from the Start Tour button or the floating help button.',
          side: 'bottom',
          align: 'center'
        }
      },
      {
        element: '.page-title',
        popover: {
          title: 'Dashboard header',
          description: 'This area shows the current page title and academic date context.',
          side: 'bottom',
          align: 'start'
        }
      },
      {
        element: '#tour-nav-dashboard',
        popover: {
          title: 'Dashboard',
          description: 'Return here anytime for summaries, alerts, and shortcuts.',
          side: 'right',
          align: 'start'
        }
      }
    ];

    if (role === 'admin') {
      steps.push(
        {
          element: '#tour-nav-students',
          popover: {
            title: 'Students',
            description: 'Manage student records, profiles, and class lists from here.',
            side: 'right',
            align: 'start'
          }
        },
        {
          element: '#tour-nav-bulk-admission',
          popover: {
            title: 'Bulk Admission',
            description: 'Use this page to admit multiple students into a class quickly.',
            side: 'right',
            align: 'start'
          }
        },
        {
          element: '#tour-nav-marks-entry',
          popover: {
            title: 'Marks Entry',
            description: 'Capture marks for assigned subjects and classes from this area.',
            side: 'right',
            align: 'start'
          }
        },
        {
          element: '#tour-nav-analytics',
          popover: {
            title: 'Analytics',
            description: 'Review deep academic analysis including charts, class comparisons, and teacher performance.',
            side: 'right',
            align: 'start'
          }
        },
        {
          element: '#tour-nav-announcements',
          popover: {
            title: 'Announcements',
            description: 'Create school-wide announcements that users can see after login and by email.',
            side: 'right',
            align: 'start'
          }
        },
        {
          element: '#tour-nav-settings',
          popover: {
            title: 'Settings',
            description: 'Update school branding, report card details, grading, and analytics thresholds here.',
            side: 'right',
            align: 'start'
          }
        }
      );
    }

    if (role === 'teacher') {
      steps.push(
        {
          element: '#tour-nav-students',
          popover: {
            title: 'Students',
            description: 'View learners linked to your teaching work.',
            side: 'right',
            align: 'start'
          }
        },
        {
          element: '#tour-nav-marks-entry',
          popover: {
            title: 'Marks Entry',
            description: 'Enter marks only for the subjects and classes assigned to you.',
            side: 'right',
            align: 'start'
          }
        },
        {
          element: '#tour-nav-class-exam-sheet',
          popover: {
            title: 'Class Exam Sheet',
            description: 'Review and export consolidated class performance sheets from here.',
            side: 'right',
            align: 'start'
          }
        },
        {
          element: '#tour-nav-analytics',
          popover: {
            title: 'Analytics',
            description: 'Inspect subject and class trends to understand performance better.',
            side: 'right',
            align: 'start'
          }
        }
      );
    }

    if (role === 'student') {
      steps.push(
        {
          element: '#tour-nav-my-profile',
          popover: {
            title: 'My Profile',
            description: 'Review your school profile and important personal academic details.',
            side: 'right',
            align: 'start'
          }
        },
        {
          element: '#tour-nav-my-results',
          popover: {
            title: 'My Results',
            description: 'Open your report slip, view performance, and download your report card here.',
            side: 'right',
            align: 'start'
          }
        },
        {
          element: '#tour-user-badge',
          popover: {
            title: 'Account area',
            description: 'Use your account area for theme changes and profile-related access.',
            side: 'left',
            align: 'start'
          }
        }
      );
    }

    steps.push({
      element: '#startTourBtn',
      popover: {
        title: 'Start tour anytime',
        description: 'You can relaunch this guided tour anytime from this button or the help button in the corner.',
        side: 'bottom',
        align: 'end'
      }
    });

    return steps.filter((step) => !step.element || document.querySelector(step.element));
  };

  const launchTour = (force = false) => {
    if (!tourConfig.enabled || !window.driver?.js?.driver) return;
    const key = `learntrack_tour_seen_${tourConfig.role || 'user'}_${tourConfig.userId || '0'}`;
    if (!force && localStorage.getItem(key) === '1') return;
    const driverObj = window.driver.js.driver({
      showProgress: true,
      animate: true,
      allowClose: true,
      overlayOpacity: 0.58,
      stagePadding: 8,
      nextBtnText: 'Next',
      prevBtnText: 'Back',
      doneBtnText: 'Finish',
      steps: buildTourSteps(tourConfig.role || 'admin'),
      onDestroyed: () => {
        localStorage.setItem(key, '1');
      }
    });
    if (window.innerWidth < 1200 && sidebar && !sidebar.classList.contains('show')) {
      openSidebar();
    }
    driverObj.drive();
  };

  document.getElementById('startTourBtn')?.addEventListener('click', function(){ launchTour(true); });
  document.getElementById('tourFab')?.addEventListener('click', function(){ launchTour(true); });
  window.setTimeout(() => launchTour(false), 500);


})();