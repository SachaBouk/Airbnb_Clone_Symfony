import { BRAND_NAME } from '../brand';

export function Footer() {
  return (
    <footer className="footer">
      <div className="container footer__inner">
        <span>© {new Date().getFullYear()} {BRAND_NAME} · Projet étudiant (TP Symfony)</span>
        <span>Français (FR) · € EUR</span>
      </div>
    </footer>
  );
}
