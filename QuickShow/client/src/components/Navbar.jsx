import React, { useState } from "react";
import { Link, useNavigate } from "react-router-dom";
import { assets } from "../assets/assets";
import { MenuIcon, SearchIcon, TicketPlus, XIcon, UserIcon } from "lucide-react";
import { useAppContext } from "../context/AppContext";

const Navbar = () => {
  const [isOpen, setIsOpen] = useState(false);

  // Переименовываем isOpen в isModalOpen
  const { isOpen: isModalOpen, setIsOpen: isModalSet } = useAppContext();

  
  // Временный флаг авторизации (позже заменишь на проверку токена из Laravel / Context)
  const [isLoggedIn, setIsLoggedIn] = useState(false);

  const navigate = useNavigate();
  const { favoriteMovies } = useAppContext();

  return (
    <div className="fixed top-0 left-0 z-50 w-full flex items-center justify-between px-6 md:px-16 lg:px-36 py-5">
      <Link to="/" className="max-md:flex-1">
        <img src={assets.logo} alt="logo" className="w-36 h-auto" />
      </Link>

      <div
        className={`max-md:absolute max-md:top-0 max-md:left-0 max-md:font-medium max-md:text-lg z-50 flex flex-col md:flex-row items-center max-md:justify-center gap-8 min-md:px-8 py-3 max-md:h-screen min-md:rounded-full backdrop-blur bg-black/70 md:bg-white/10 md:border border-gray-300/20 overflow-hidden transition-[width] duration-300 ${
          isOpen ? "max-md:w-full" : "max-md:w-0"
        }`}
      >
        <XIcon
          className="md:hidden absolute top-6 right-6 w-6 h-6 cursor-pointer"
          onClick={() => setIsOpen(!isOpen)}
        />

        <Link
          onClick={() => {
            scrollTo(0, 0);
            setIsOpen(false);
          }}
          to="/"
        >
          Home
        </Link>
        <Link
          onClick={() => {
            scrollTo(0, 0);
            setIsOpen(false);
          }}
          to="/movies"
        >
          Movies
        </Link>
        <Link
          onClick={() => {
            scrollTo(0, 0);
            setIsOpen(false);
          }}
          to="/"
        >
          Releases
        </Link>
        {favoriteMovies.length > 0 && (
          <Link
            onClick={() => {
              scrollTo(0, 0);
              setIsOpen(false);
            }}
            to="/favorite"
          >
            Favorites
          </Link>
        )}
      </div>

      <div className="flex items-center gap-6">
        <SearchIcon className="max-md:hidden w-6 h-6 cursor-pointer" />

        {!isLoggedIn ? (
          <button
            onClick={() => isModalSet(!isModalOpen)}
            className="px-4 py-1 sm:px-7 sm:py-2 bg-primary hover:bg-primary-dull transition rounded-full font-medium cursor-pointer"
          >
            Login
          </button>
        ) : (
          <div className="flex items-center gap-4">
            <button
              onClick={() => navigate("/my-bookings")}
              className="flex items-center gap-2 px-4 py-2 bg-white/10 hover:bg-white/20 transition rounded-full text-sm cursor-pointer"
            >
              <TicketPlus width={16} />
              <span className="max-sm:hidden">My Bookings</span>
            </button>
            <button
              onClick={() => setIsLoggedIn(false)}
              className="p-2 bg-gray-800 rounded-full hover:bg-gray-700 transition cursor-pointer"
              title="Logout"
            >
              <UserIcon className="w-5 h-5" />
            </button>
          </div>
        )}
      </div>

      <MenuIcon
        onClick={() => setIsOpen(!isOpen)}
        className="max-md:ml-4 md:hidden w-8 h-8 cursor-pointer"
      />
    </div>
  );
};

export default Navbar;