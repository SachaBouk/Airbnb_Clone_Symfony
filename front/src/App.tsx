import { Link, Route, Routes } from 'react-router-dom';
import { useAuth } from './auth/AuthContext';
import { Footer } from './components/Footer';
import { Header } from './components/Header';
import { LoginModal } from './components/LoginModal';
import { AccountPage } from './pages/AccountPage';
import { FeedPage } from './pages/FeedPage';
import { HomePage } from './pages/HomePage';
import { ListingFormPage } from './pages/ListingFormPage';
import { ListingPage } from './pages/ListingPage';
import { TripsPage } from './pages/TripsPage';

export function App() {
  const { isLoginOpen } = useAuth();

  return (
    <>
      <Header />
      <Routes>
        <Route path="/" element={<HomePage />} />
        <Route path="/listings/:id" element={<ListingPage />} />
        <Route path="/host/listings/new" element={<ListingFormPage />} />
        <Route path="/host/listings/:id/edit" element={<ListingFormPage />} />
        <Route path="/trips" element={<TripsPage />} />
        <Route path="/feed" element={<FeedPage />} />
        <Route path="/account" element={<AccountPage />} />
        <Route
          path="*"
          element={
            <main className="container empty-state">
              <h2>Page introuvable</h2>
              <Link to="/" className="button button--dark">Retour à l’accueil</Link>
            </main>
          }
        />
      </Routes>
      <Footer />
      {isLoginOpen && <LoginModal />}
    </>
  );
}
