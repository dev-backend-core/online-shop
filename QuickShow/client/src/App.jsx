import Navbar from "./components/Navbar";
import { Route, Routes, useLocation } from "react-router-dom";
import Home from "./pages/Home";
import Movies from "./pages/Movies";
import MovieDetails from "./pages/MovieDetails";
import SeatLayout from "./pages/SeatLayout";
import MyBookings from "./pages/MyBookings";
import Favorite from "./pages/Favorite";
import { Toaster } from "react-hot-toast";
import Footer from "./components/Footer";
import Loading from "./components/Loading";
import { AuthModal } from "./components/AuthModal";
import { useAppContext } from "./context/AppContext";
import { Fragment } from "react";

const App = () => {
  const isAdminRoute = useLocation().pathname.startsWith("/admin");
  const {isOpen} = useAppContext();

  return (
    <>
     {isOpen && (<Fragment><AuthModal /></Fragment>)}
     
      <Toaster />
      {!isAdminRoute && <Navbar />}
      <Routes>
        <Route path="/" element={<Home />} />
        <Route path="/movies" element={<Movies />} />
        <Route path="/movies/:id" element={<MovieDetails />} />
        <Route path="/movies/:id/:date" element={<SeatLayout />} />
        
        <Route path="/my-bookings" element={<MyBookings />} />
        <Route path="/loading/:nextUrl" element={<Loading />} />
        <Route path="/favorite" element={<Favorite />} />
      </Routes>
      {!isAdminRoute && <Footer />}
    </>
  );
};

export default App;
