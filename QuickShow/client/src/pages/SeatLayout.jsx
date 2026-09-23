import { useEffect, useState,useMemo,useCallback } from "react";
import { useNavigate, useParams } from "react-router-dom";
import { assets } from "../assets/assets";
import Loading from "../components/Loading";
import { ArrowRightIcon, ClockIcon } from "lucide-react";
import isoTimeFormat from "../lib/isoTimeFormat";
import BlurCircle from "../components/BlurCircle";
import toast from "react-hot-toast";
import { useAppContext } from "../context/AppContext";
import api from "../api/axios";

const SeatLayout = () => {
  const groupRows = [
    ["A", "B"],
    ["C", "D"],
    ["E", "F"],
    ["G", "H"],
    ["I", "J"],
  ];

  const { id, date } = useParams();
  const [selectedSeats, setSelectedSeats] = useState([]);
  const [selectedTime, setSelectedTime] = useState(null);
  const [show, setShow] = useState(null);
  const [seatsFromDb,setSeatsFromDb] = useState(null)
  const [occupiedSeats, setOccupiedSeats] = useState([]);

  const navigate = useNavigate();

  const { axios, getToken, user } = useAppContext();

  const getShow = async () => {
    try {
      const [seatsRes, showRes] = await Promise.all([
        axios.get('/api/seats'),
        axios.get(`/api/show/${id}`)
      ]);

      if (showRes.data.success) {
        setShow(showRes.data.movie.shows);
        setSeatsFromDb(seatsRes.data.seat);
      }
    } catch (error) {
      console.log(error);
    }
  };

  const handleSeatClick = (seatId) => {
    // console.log(seatId);
    if (!selectedTime) {
      return toast("Please select time first");
    }
    if (!selectedSeats.includes(seatId) && selectedSeats.length > 4) {
      return toast("You can only select 5 seats");
    }
    if (occupiedSeats.includes(seatId)) {
      return toast("This seat is already booked");
    }
    setSelectedSeats((prev) =>
      prev.includes(seatId)
        ? prev.filter((seat) => seat !== seatId)
        : [...prev, seatId]
    );
  };

  // console.log(selectedTime.id);

  const seatMap = useMemo(() => {
    const map = {};
    seatsFromDb?.forEach((seat) => {
      map[`${seat.row_number}_${seat.seat_number}`] = seat;
    });
    return map;
  }, [seatsFromDb]);

  // const renderSeats = (row, count = 9) => (
  //   <div key={row} className="flex gap-2 mt-2">
  //     <div className="flex flex-wrap items-center justify-center gap-2">
  //       {Array.from({ length: count }, (_, i) => {
  //         const seatId = `${row}${i + 1}`;
  //         return (
  //           <button
  //             key={seatId}
  //             onClick={() => handleSeatClick(seatId)}
  //             className={`h-8 w-8 rounded border border-primary/60 cursor-pointer ${
  //               selectedSeats.includes(seatId) && "bg-primary text-white"
  //             } ${occupiedSeats.includes(seatId) && "opacity-50"}`}
  //           >
  //             {seatId}
  //           </button>
  //         );
  //       })}
  //     </div>
  //   </div>
  // );


  const renderSeats = (row, count = 9) => {
    // 1. Превращаем букву ряда в число для поиска в БД: 'A' -> 1, 'B' -> 2, 'C' -> 3 ...
    const rowNum = row.charCodeAt(0) - 64;
    return (
      <div key={row} className="flex items-center gap-2 mt-2">
        {/* Буква ряда */}
        <div className="flex flex-wrap items-center justify-center gap-2">
          {Array.from({ length: count }, (_, i) => {
            const seatNumber = i + 1;
            
            // Мгновенный поиск из prepared-карты
            const dbSeat = seatMap[`${rowNum}_${seatNumber}`];

            // Если места нет в БД — отрисуем пустую заглушку для сохранения сетки или null
            if (!dbSeat) return null;

            const seatId = dbSeat.id;
            const isSelected = selectedSeats.includes(seatId);
            const isOccupied = occupiedSeats?.includes(seatId); // Добавили проверку на занятость

            return (
              <button
                key={seatId}
                disabled={isOccupied}
                onClick={() => handleSeatClick(seatId)}
                className={`h-8 w-8 rounded border text-xs flex items-center justify-center transition-all ${
                  isOccupied
                    ? "border-gray-700 bg-gray-800 text-gray-600 cursor-not-allowed opacity-50"
                    : isSelected
                    ? "bg-primary border-primary text-white font-bold cursor-pointer scale-105"
                    : "border-primary/60 hover:bg-primary/20 text-gray-300 cursor-pointer"
                }`}
              >
                {`${row}${seatNumber}`}
              </button>
            );
          })}
        </div>
      </div>
    );
  };

  // const getOccupiedSeats = async () => {
  //   try {
  //     //id сеанса 
  //     const { data } = await axios.get(
  //       `/api/booking/seats/${selectedTime.id}`
  //     );
  //     if (data.success) {
  //       setOccupiedSeats(data.occupiedSeats);
  //     } else {
  //       toast.error(data.message);
  //     }
  //   } catch (error) {
  //     console.log(error);
  //   }
  // };

  const getOccupiedSeats = useCallback(async () => {
    if (!selectedTime?.id) return;

    try {
      // Добавляем timestamp (_t), чтобы Axios и браузер ТОЧНО не кэшировали ответ
      const { data } = await axios.get(
        `/api/booking/seats/${selectedTime.id}?_t=${Date.now()}`
      );
      
      if (data.success) {
        setOccupiedSeats(data.occupiedSeats);
      } else {
        toast.error(data.message);
      }
    } catch (error) {
      console.log(error);
    }
  }, [selectedTime?.id]);

  useEffect(() => {
    getOccupiedSeats();

    // 2. Слушаем возврат по кнопке "Назад" (BFcache)
    const handlePageShow = (event) => {
      // event.persisted === true означает, что страница была восстановлена из кэша браузера
      if (event.persisted) {
        getOccupiedSeats();
      }
    };

    window.addEventListener('pageshow', handlePageShow);

    return () => {
      window.removeEventListener('pageshow', handlePageShow);
    };
  }, [getOccupiedSeats]);
  

  const bookTickets = async () => {
    try {
      if (!user) return toast.error("Please login to proceed");

      if (!selectedTime || !selectedSeats.length)
        return toast.error("Please select a time and seats");

      const { data } = await api.post(
        "/api/booking/create",
        { showId: selectedTime.id, 
          selectedSeats,
        },
      );

      if (data.success) {
        window.location.href = data.url;
      } else {
        toast.error(data.message);
      }
    } catch (error) {
      toast.error(error.message);
    }
  };

  useEffect(() => {
    getShow();
  }, []);

  // console.log(seatMap)

  useEffect(() => {
    if (selectedTime) {
      getOccupiedSeats();
    }
  }, [selectedTime]);

  return show ? (
    <div className="flex flex-col md:flex-row px-6 md:px-16 lg:px-40 py-30 md:pt-50">
      {/* Available Timings */}
      <div className="w-60 bg-primary/10 border border-primary/20 rounded-lg py-10 h-max md:sticky md:top-30">
        <p className="text-lg font-semibold px-6">Available Timings</p>
        <div className="mt-5 space-y-1">
          {show.map((item) => (
            <div
              key={item.id}
              onClick={() => setSelectedTime(item)}
              className={`flex items-center gap-2 px-6 py-2 w-max rounded-r-md cursor-pointer transition ${
                selectedTime?.start_time === item.start_time
                  ? "bg-primary text-white"
                  : "hover:bg-primary/20"
              }`}
            >
              <ClockIcon className="w-4 h-4" />
              <p className="text-sm">{isoTimeFormat(item.start_time)}</p>
            </div>
          ))}
        </div>
      </div>

      {/* Seats Layout */}
      <div className="relative flex-1 flex flex-col items-center max-md:mt-16">
        <BlurCircle top="-100px" left="-100px" />
        <BlurCircle bottom="0" right="0" />
        <h1 className="text-2xl font-semibold mb-4">Select your seat</h1>
        <img src={assets.screenImage} alt="screen" />
        <p className="text-gray-400 text-sm mb-6">SCREEN SIDE</p>
        <div className="flex flex-col items-center mt-10 text-xs text-gray-300">
          <div className="grid grid-cols-2 md:grid-cols-1 gap-8 md:gap-2 mb-6">
            {groupRows[0].map((row) => renderSeats(row))}
          </div>
          <div className="grid grid-cols-2 gap-11">
            {groupRows.slice(1).map((group, idx) => (
              <div key={idx}>{group.map((row) => renderSeats(row))}</div>
            ))}
          </div>
        </div>

        <button
          onClick={bookTickets}
          className="flex items-center gap-1 mt-20 px-10 py-3 text-sm bg-primary hover:bg-primary-dull transition rounded-full font-medium cursor-pointer active:scale-95"
        >
          Proceed to Checkout
          <ArrowRightIcon strokeWidth={3} className="w-4 h-4" />
        </button>
      </div>
    </div>
  ) : (
    <Loading />
  );
};

export default SeatLayout;
