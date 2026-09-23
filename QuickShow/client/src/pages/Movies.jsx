import BlurCircle from "../components/BlurCircle";
import MovieCard from "../components/MovieCard";
import { useAppContext } from "../context/AppContext";
import React, { useState,useRef, useEffect } from "react";
import { SearchIcon, XIcon } from "lucide-react";
import {useDebounce} from '../lib/useDebounce'
import api from "../api/axios";

const Movies = () => {
  const { shows,setShows,fetchShows } = useAppContext();
  const [searchQuery, setSearchQuery] = useState('');
  const searchInputRef = useRef(null);
  const [isLoading, setIsLoading] = useState(false);
  const [filteredShows, setFilteredShows] = useState(shows);

  const debouncedSearchQuery = useDebounce(searchQuery, 500);

  const handleSearchSubmit = (e) => {
    e.preventDefault();
    if (searchQuery.trim()) {
      setSearchQuery('');
    }
  };

  useEffect(() => {
    fetchShows();
  }, []); 

  useEffect(() => {
    if (!debouncedSearchQuery.trim()) {
      setFilteredShows(shows);
    }
  }, [shows]);

  useEffect(()=>{
    const trimmedQuery = debouncedSearchQuery.trim().toLowerCase();

    if (!trimmedQuery) {
      setFilteredShows(shows);
      return;
    }

    const result = shows.filter((item) =>
      item.title.toLowerCase().includes(trimmedQuery)
    );

    if(result.length > 0){
      setFilteredShows(result);
    }else{
      const fetchFromBackend = async () => {
      setIsLoading(true);
      try {

        const {data} = await api.post(`api/movie/search`,
          {'searchValue': trimmedQuery});

        if (data.success && data.movies.length > 0) {
          console.log(data.movies)
          setFilteredShows(data.movies);
        } else {
          setFilteredShows([]);
        }
      } catch (error) {
        console.error('Ошибка поиска фильма на сервере:', error);
        setFilteredShows([]);
      } finally {
        setIsLoading(false);
      }
    };

    fetchFromBackend();
    }
  },[debouncedSearchQuery])

  return (
    <div className="relative my-40 mb-60 px-6 md:px-16 lg:px-40 xl:px-44 overflow-hidden min-h-[80vh]">
      <BlurCircle top="150px" left="0" />
      <BlurCircle bottom="50px" right="50px" />
      <div style={{display:'flex',marginBottom:'1rem'}}>
        <h1 className="text-lg font-medium my-4">Now Showing</h1>
        <div style={{marginLeft:'4rem'}} className="flex items-center gap-6">
            {/* Поисковая строка с анимацией расширения */}
            <div className="flex items-center">
              <form 
                onSubmit={handleSearchSubmit} 
                className="flex items-center gap-2 bg-white/10 border border-gray-300/20 rounded-full px-3 py-1.5 transition-all duration-300 w-48 lg:w-64"
              >
                <SearchIcon className="w-4 h-4 opacity-60 shrink-0" />
                <input
                  ref={searchInputRef}
                  type="text"
                  value={searchQuery}
                  onChange={(e) => setSearchQuery(e.target.value)}
                  placeholder="Search movies..."
                  className="w-full bg-transparent text-sm text-white placeholder-gray-400 focus:outline-none"
                />
                <XIcon 
                  onClick={() => {
                    setSearchQuery('');
                  }}
                  className="w-4 h-4 cursor-pointer opacity-60 hover:opacity-100 shrink-0"
                />
              </form>
            </div>
        </div>
      </div> 

      {filteredShows.length > 0 ? ( 
        <div className="flex flex-wrap max-sm:justify-center gap-8">
          {filteredShows.map((movie) => (
            <MovieCard movie={movie} key={movie._id} />
          ))}
        </div> ) : (
        <div className="flex flex-col items-center justify-center h-screen">
          <h1 className="text-3xl font-bold text-center">No movies available</h1>
        </div>
      )}
    </div>
  )
};

export default Movies;
