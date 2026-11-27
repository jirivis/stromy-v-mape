/* Keep popup text and actions clear of Leaflet controls on narrow screens. */
(function () {
    'use strict';
    L.Map.addInitHook(function () {
        const map = this;
        const hidden = new Map();
        let frame = null;
        function restore(control, saved) {
            if (saved.value) control.style.setProperty('visibility', saved.value, saved.priority);
            else control.style.removeProperty('visibility');
        }
        function update() {
            frame = null;
            const popups = Array.from(map.getContainer().querySelectorAll('.leaflet-popup'))
                .map(el => el.getBoundingClientRect()).filter(r => r.width && r.height);
            const controls = map.getContainer().querySelectorAll('.leaflet-control');
            controls.forEach(control => {
                const r = control.getBoundingClientRect();
                const overlaps = popups.some(p => r.width && r.height &&
                    r.left < p.right + 6 && r.right > p.left - 6 &&
                    r.top < p.bottom + 6 && r.bottom > p.top - 6);
                if (overlaps && !hidden.has(control)) {
                    hidden.set(control, {value: control.style.getPropertyValue('visibility'),
                        priority: control.style.getPropertyPriority('visibility')});
                    control.style.setProperty('visibility', 'hidden', 'important');
                } else if (!overlaps && hidden.has(control)) {
                    restore(control, hidden.get(control));
                    hidden.delete(control);
                }
            });
            hidden.forEach((saved, control) => {
                if (!map.getContainer().contains(control)) {
                    restore(control, saved);
                    hidden.delete(control);
                }
            });
        }
        function schedule() {
            if (frame === null) frame = requestAnimationFrame(update);
        }
        const observer = typeof ResizeObserver !== 'undefined' ? new ResizeObserver(schedule) : null;
        map.on('popupopen', e => {
            if (observer && e.popup.getElement()) observer.observe(e.popup.getElement());
            schedule();
        });
        map.on('popupclose', e => {
            if (observer && e.popup.getElement()) observer.unobserve(e.popup.getElement());
            schedule();
        });
        map.on('move zoom resize', schedule);
        map.on('unload', () => {
            if (frame !== null) cancelAnimationFrame(frame);
            if (observer) observer.disconnect();
            hidden.forEach((saved, control) => restore(control, saved));
            hidden.clear();
        });
    });
}());
