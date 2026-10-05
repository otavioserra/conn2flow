import {readFileSync} from 'node:fs';
import {describe, it, expect} from 'vitest';

describe.each(['pt-br', 'en'])('dashboard header aliases (%s)', (language) => {
    it('gives cover and icon the Access destination and keeps drag outside all links', () => {
        const source = readFileSync(`gestor/modulos/dashboard/resources/${language}/components/dashboard-cards-tailwind/dashboard-cards-tailwind.html`, 'utf8');
        document.body.innerHTML = source;
        const card = document.querySelector('.dashboard-module-card');
        const headerLink = card.querySelector('.dashboard-card-header-link');
        const access = card.querySelector('.dashboard-card-actions-left a');
        expect(headerLink).not.toBeNull();
        expect(headerLink.getAttribute('href')).toBe(access.getAttribute('href'));
        expect(headerLink.getAttribute('aria-label')).toBe('@[[cards-btn-acessar]]@ #modulo-nome#');
        expect(headerLink.querySelector('.dashboard-card-cover-wrapper')).not.toBeNull();
        expect(headerLink.querySelector('.dashboard-card-svg')).not.toBeNull();
        const handle = card.querySelector('.dashboard-card-drag-handle');
        expect(handle.closest('a')).toBeNull();
        expect(handle.style.zIndex).toBe('20');
    });
});
