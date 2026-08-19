import Echo from "laravel-echo";
import Pusher from "pusher-js";
import axios from "axios";

// -----------------------------
// Extend window for Pusher & Echo
// -----------------------------
declare global {
  interface Window {
    Pusher?: typeof Pusher;
    Echo?: Echo<any>;
  }
}

// -----------------------------
// Axios fetchDashboard function
// -----------------------------
export const fetchDashboard = async (range: string = "30d") => {
  const token = localStorage.getItem("token");

  if (!token) {
    console.error("No auth token found");
    return;
  }

  try {
    const response = await axios.get(
      `${import.meta.env.VITE_API_URL}/api/admin/dashboard?range=${range}`,
      {
        headers: {
          Authorization: `Bearer ${token}`,
        },
      },
    );

    return response.data;
  } catch (err: any) {
    console.error(
      "Error fetching dashboard:",
      err.response?.status,
      err.response?.data,
    );
  }
};

// -----------------------------
// Pusher / Laravel Echo setup
// -----------------------------

window.Pusher = Pusher;
Pusher.logToConsole = true;

// Type-safe authorizer
const authorizer = (channel: any) => {
  return {
    authorize: (
      socketId: string,
      callback: (error: Error | null, data?: any) => void,
    ) => {
      const token = localStorage.getItem("token");

      if (!token) {
        return callback(new Error("No token"));
      }

      fetch(`${import.meta.env.VITE_API_URL}/broadcasting/auth`, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          Authorization: `Bearer ${token}`,
        },
        body: JSON.stringify({
          socket_id: socketId,
          channel_name: channel.name,
        }),
      })
        .then((res) => {
          if (!res.ok) {
            throw new Error(`${res.status} ${res.statusText}`);
          }

          return res.json();
        })
        .then((data) => callback(null, data))
        .catch((err: Error) => callback(err));
    },
  };
};

// -----------------------------
// Initialize Echo
// -----------------------------
const token = localStorage.getItem("token");

const echo = new Echo({
  broadcaster: "pusher",
  key: import.meta.env.VITE_PUSHER_APP_KEY,
  cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER || "mt1",

  wsHost: import.meta.env.VITE_PUSHER_HOST,
  wsPort: Number(import.meta.env.VITE_PUSHER_PORT),

  forceTLS: import.meta.env.VITE_PUSHER_SCHEME === "https",

  enabledTransports: ["ws"],

  enableStats: false,

  authEndpoint: `${import.meta.env.VITE_API_URL}/api/broadcasting/auth`,

  auth: {
    headers: {
      Authorization: `Bearer ${token}`,
      Accept: "application/json",
    },
  },
});

window.Echo = echo;

export default echo;