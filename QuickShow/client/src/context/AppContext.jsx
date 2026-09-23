import { createContext, useContext, useEffect, useState } from "react";
import axios from "axios";
import { useLocation, useNavigate } from "react-router-dom";
import toast from "react-hot-toast";
import api from '../api/axios';

axios.defaults.baseURL = import.meta.env.VITE_BASE_URL;

export const AppContext = createContext();

export const AppProvider = ({ children }) => {
  const [isAdmin, setIsAdmin] = useState(false);
  const [shows, setShows] = useState([]);
  const [favoriteMovies, setFavoriteMovies] = useState([]);
  const [user, setUser] = useState(null);

  const [isOpen, setIsOpen] = useState(false);

  const image_base_url = import.meta.env.VITE_TMDB_IMAGE_BASE_URL;

  const location = useLocation();
  const navigate = useNavigate();

  // Достаём токен авторизации из localStorage (замена Clerk)
  const getToken = () => localStorage.getItem("token");

  const fetchIsAdmin = async () => {

    const {data} = await api.get("/api/user");
    if (!data) return;

    try {
      setUser(data);
      setIsAdmin(data);

      if (!data && location.pathname.startsWith("/admin")) {
        navigate("/");
        toast.error("You are not authorized to access admin dashboard");
      }
    } catch (error) {
      console.error(error);
    }
  };

  const fetchShows = async () => {
    try {
      const { data } = await axios.get("/api/show/all");

      // console.log(data)

      if (data.success) {
        setShows(data.shows);
      } else {
        toast.error(data.message);
      }
    } catch (error) {
      console.error(error);
    }
  };

  const fetchFavoriteMovies = async () => {
    const {data} = await api.get("/api/user/favorites");
    if (!data) return;

    try {
      if (data.success) {
        setFavoriteMovies(data.movies);
      } else {
        toast.error(data.message);
      }
    } catch (error) {
      console.error(error);
    }
  };

  useEffect(() => {
    fetchShows();
    fetchIsAdmin();
  }, []);

  useEffect(() => {
    if (isAdmin) {
      fetchIsAdmin();
      fetchFavoriteMovies();
    }
  }, []);

  const value = {
    axios,
    fetchIsAdmin,
    user,
    setUser,
    getToken,
    navigate,
    isAdmin,
    setIsAdmin,
    shows,
    setShows,
    favoriteMovies,
    setFavoriteMovies,
    fetchFavoriteMovies,
    image_base_url,
    isOpen,
    setIsOpen,
    fetchShows
  };

  return <AppContext.Provider value={value}>{children}</AppContext.Provider>;
};

export const useAppContext = () => useContext(AppContext);