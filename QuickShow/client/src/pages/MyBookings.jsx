import { useEffect, useState } from "react";
import Loading from "../components/Loading";
import BlurCircle from "../components/BlurCircle";
import timeFormat from "../lib/timeFormat";
import { dateFormat } from "../lib/dateFormat";
import { useAppContext } from "../context/AppContext";
import { Link } from "react-router-dom";
import api from "../api/axios";

const MyBookings = () => {
  const currency = import.meta.env.VITE_CURRENCY;

  const { user } = useAppContext();

  const [bookings, setBookings] = useState([]);
  const [isLoading, setIsLoading] = useState(true);

  const getMyBookings = async () => {
    try {
      const { data } = await api.get("/api/user/bookings");

      if (data.success) {
        
        setBookings(data.bookings);
      }
    } catch (error) {
      console.log(error);
    }
    setIsLoading(false);
  };

  const createStripeSession = async (bookingDate) => {
    try {
      const { data } = await api.post("/api/booking/createStripeSession", {
        booking_date: bookingDate,
      });

      if (data.url) {
        window.location.href = data.url;
      }
    } catch (error) {
      console.error("Payment error:", error);
    }
  };

  useEffect(() => {
    if (user) {
      getMyBookings();
    }
  }, [user]);

// console.log(count(bookings[0].seat_id))

  return !isLoading ? (
    <div className="relative px-6 md:px-16 lg:px-40 pt-30 md:pt-40 min-h-[80vh]">
      <BlurCircle top="100px" left="100px" />
      <div>
        <BlurCircle bottom="0px" left="600px" />
      </div>
      <h1 className="text-lg font-semibold mb-4">My Bookings</h1>

      {bookings.length === 0 
      ? <p>Фильмов нет</p> 
      : bookings.map((item, index) => (
        <div
          key={index}
          className="flex flex-col md:flex-row justify-between bg-primary/8 border border-primary/20 rounded-lg mt-4 p-2 max-w-3xl"
        >
          <div className="flex flex-col md:flex-row">
            <img
              src={item.movie.poster_preview_url}
              alt="poster"
              className="md:max-w-45 aspect-video h-auto object-cover object-bottom rounded"
            />
            <div className="flex flex-col p-4">
              <p className="text-lg font-semibold">{item.movie.title}</p>
              <p className="text-gray-400 text-sm">
                {timeFormat(item.movie.duration_min)}
              </p>
              <p style={{width:'12rem'}} className="text-gray-400 text-sm mt-auto">
                {dateFormat(item.show.start_time)}
              </p>
            </div>
          </div>

          <div className="flex flex-col md:items-end md:text-right justify-between p-4">
            <div className="flex items-center gap-4">
              <p className="text-2xl font-semibold mb-3">
                {`$${item.total_price}`}
              </p>
              {item.status === 'reserved' &&  (
                <p
                  onClick={() => createStripeSession((item.booking_date))}
                  className="bg-primary px-4 py-1.5 mb-3 text-sm rounded-full font-medium cursor-pointer"
                >
                  Pay Now
                </p>
              )}
            </div>
            <div style={{width:'12rem'}} className="text-sm">
              <p>
                <span className="text-gray-400">Total Tickets:</span>{" "}
                {item.total_seats}
              </p>
              <p>
                <span className="text-gray-400">Seat Number:</span>{" "}
                {item.seats_list}
              </p>
            </div>
          </div>
        </div>
      ))
      }

    </div>
  ) : (
    <Loading />
  );
};

export default MyBookings;
