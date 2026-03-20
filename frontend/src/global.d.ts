// src/global.d.ts
import Pusher from "pusher-js";
import Echo from "laravel-echo";

declare global {
  interface Window {
    Pusher?: typeof Pusher;
    Echo?: Echo;
  }
}
