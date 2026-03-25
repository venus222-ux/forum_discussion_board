import { BrowserRouter, Routes, Route, Navigate } from "react-router-dom";
import { lazy, Suspense, useEffect } from "react";
import Navbar from "./components/Navbar";
import { ToastContainer } from "react-toastify";
import { useStore } from "./store/useStore";

// Lazy-loaded pages
const Login = lazy(() => import("./pages/Login"));
const Register = lazy(() => import("./pages/Register"));
const Profile = lazy(() => import("./pages/Profile"));
const Home = lazy(() => import("./pages/Home"));
const ForgotPassword = lazy(() => import("./pages/ForgetPassword"));
const ResetPassword = lazy(() => import("./pages/ResetPassword"));
const NotFound = lazy(() => import("./pages/NotFound"));
const CategoryList = lazy(() => import("./pages/CategoryList"));
const ThreadList = lazy(() => import("./pages/ThreadList"));
const ThreadDetail = lazy(() => import("./pages/ThreadDetail"));
const Dashboard = lazy(() => import("./pages/Dashboard/Dashboard"));

const App = () => {
  const { theme, isAuth, startTokenRefreshLoop } = useStore();

  useEffect(() => {
    document.documentElement.setAttribute("data-bs-theme", theme);
    if (isAuth) startTokenRefreshLoop();
  }, [theme, isAuth]);

  return (
    <BrowserRouter>
      <Navbar />
      <Suspense
        fallback={
          <div style={{ textAlign: "center", marginTop: "2rem" }}>
            <div className="spinner-border text-primary" role="status">
              <span className="visually-hidden">Loading...</span>
            </div>
            <p>Loading page...</p>
          </div>
        }
      >
        <Routes>
          <Route path="/" element={<Home />} />
          <Route
            path="/login"
            element={!isAuth ? <Login /> : <Navigate to="/dashboard" replace />}
          />
          <Route
            path="/register"
            element={
              !isAuth ? <Register /> : <Navigate to="/dashboard" replace />
            }
          />
          <Route path="/categories" element={<CategoryList />} />
          <Route
            path="/categories/:categorySlug/threads"
            element={<ThreadList />}
          />
          <Route path="/threads/:slug" element={<ThreadDetail />} />

          {/* Single /dashboard route with role selection */}
          {/* 🔥 CLEAN SINGLE DASHBOARD ROUTE */}
          <Route path="/dashboard" element={<Dashboard />} />

          <Route path="/forgot-password" element={<ForgotPassword />} />
          <Route path="/reset-password/:token" element={<ResetPassword />} />
          <Route
            path="/profile"
            element={isAuth ? <Profile /> : <Navigate to="/login" replace />}
          />
          <Route path="*" element={<NotFound />} />
        </Routes>
      </Suspense>
      <ToastContainer position="top-right" autoClose={3000} />
    </BrowserRouter>
  );
};

export default App;
