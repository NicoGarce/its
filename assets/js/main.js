// assets/js/main.js - Main JS file
document.addEventListener('DOMContentLoaded', function() {
    // Sidebar functionality
    const sidebar = document.getElementById('sidebar');
    const submenuToggles = document.querySelectorAll('.has-submenu > .sidebar-link');

    // Sidebar collapse is now controlled by the toggle button (no hover behavior)
    // (toggle behavior handled in sidebar.php inline script which persists state)

    // Submenu toggle
    submenuToggles.forEach(toggle => {
        toggle.addEventListener('click', function(e) {
            e.preventDefault();
            const parent = this.parentElement;
            const submenu = parent.querySelector('.submenu');
            parent.classList.toggle('open');
            submenu.classList.toggle('show');
        });
    });

    // Set active menu item
    const currentPage = window.location.pathname.split('/').pop();
    const menuLinks = document.querySelectorAll('.sidebar-link');
    menuLinks.forEach(link => {
        if (link.getAttribute('href') === currentPage) {
            link.classList.add('active');
        }
    });

    // Smooth transitions for all elements
    const style = document.createElement('style');
    style.textContent = `
        * {
            transition: all 0.3s ease;
        }
    `;
    document.head.appendChild(style);

    // Global session notifier: poll for new sessions and show overlay once per session
    (function(){
        const API = 'api/chat_sessions.php';
        const POLL_MS = 5000;
        const notifiedKey = 'its_notified_sessions_v1';
        function getNotified(){ try{ return JSON.parse(localStorage.getItem(notifiedKey) || '[]'); }catch(e){return [];} }
        function addNotified(id){ try{ const arr = getNotified(); if(!arr.includes(id)){ arr.push(id); localStorage.setItem(notifiedKey, JSON.stringify(arr)); } }catch(e){} }
        async function poll(){
            try{
                const r = await fetch(API, { cache: 'no-store' });
                if(!r.ok) return;
                const data = await r.json();
                const sessions = data && data.sessions ? data.sessions : data || {};
                const activeIds = Object.keys(sessions).filter(id=>{ const s = sessions[id]; return s && !(s.ended == 1 || s.ended === true || s.status === 'ended'); });
                const notified = getNotified();
                for(const id of activeIds){
                    if(notified.includes(id)) continue;
                    // new session detected
                    try{ showSessionNotif(id, sessions[id]); }catch(e){console.warn('showSessionNotif failed', e);} 
                    addNotified(id);
                }
            }catch(e){ /* ignore */ }
        }

        function showSessionNotif(sessionId, sessionData){
            const overlay = document.getElementById('sessionNotif');
            const nBody = document.getElementById('nBody');
            const audio = document.getElementById('notifAudio');
            if(!overlay || !nBody) return;
            const concern = (sessionData && sessionData.issue) ? sessionData.issue : '';
            const room = (sessionData && sessionData.location) ? sessionData.location : '';
            // display concern first, then room/department
            let txt = concern ? concern : (sessionData && sessionData.name ? sessionData.name : 'New session');
            if(room) txt += ' — ' + room;
            nBody.textContent = txt;
            overlay.classList.remove('notif-hidden');
            try{ audio.currentTime = 0; audio.volume = 0.9; const p = audio.play(); if(p && p.catch) p.catch(()=>{}); }catch(e){}
            const openBtn = document.getElementById('nOpen');
            const dismissBtn = document.getElementById('nDismiss');
            if(openBtn){ openBtn.onclick = function(){ try{ localStorage.setItem('its_open_session', sessionId); window.location.href = 'tech_chat.php'; }catch(e){} }; }
            if(dismissBtn){ dismissBtn.onclick = function(){ overlay.classList.add('notif-hidden'); }; }
            setTimeout(()=>{ try{ overlay.classList.add('notif-hidden'); }catch(e){} }, 10000);
        }

        // start polling
            poll(); setInterval(poll, POLL_MS);

        // No anti-inspect handlers — removed per user request.

        })();
    });