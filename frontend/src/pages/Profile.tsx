// src/pages/Profile.tsx
import { useEffect, useState, type ChangeEvent, type FormEvent } from "react";
import API from "../api";
import { toast } from "react-toastify";
import { useStore } from "../store/useStore";
import styles from "../styles/Profile.module.css";

import type { ProfileData, ProfileUpdateRequest } from "../types/auth";

const Profile = () => {
  const [profile, setProfile] = useState<ProfileData | null>(null);

  const [formData, setFormData] = useState<ProfileUpdateRequest>({
    email: "",
    password: "",
    password_confirmation: "",
  });

  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [updating, setUpdating] = useState(false);

  const setIsAuth = useStore((state) => state.setIsAuth);

  useEffect(() => {
    API.get("/profile")
      .then((res) => {
        setProfile(res.data);

        setFormData((prev) => ({
          ...prev,
          email: res.data.email || "",
        }));

        setLoading(false);
      })
      .catch(() => {
        toast.error("Failed to load profile");
        setError("Failed to load profile");
        setLoading(false);
      });
  }, []);

  const handleChange = (e: ChangeEvent<HTMLInputElement>) => {
    setFormData((prev) => ({
      ...prev,
      [e.target.name]: e.target.value,
    }));
  };

  const handleUpdate = (e: FormEvent) => {
    e.preventDefault();

    if (
      formData.password &&
      formData.password !== formData.password_confirmation
    ) {
      toast.error("Passwords do not match");
      return;
    }

    setUpdating(true);

    API.put("/profile", formData)
      .then((res) => {
        toast.success(res.data.message || "Profile updated");

        setProfile((prev) =>
          prev ? { ...prev, email: formData.email } : prev
        );

        setFormData((prev) => ({
          ...prev,
          password: "",
          password_confirmation: "",
        }));
      })
      .catch((err) => {
        toast.error(err.response?.data?.message || "Update failed");
      })
      .finally(() => setUpdating(false));
  };

  const handleDelete = () => {
    if (
      !window.confirm(
        "Are you sure? This will permanently delete your account."
      )
    )
      return;

    API.delete("/profile")
      .then(() => {
        toast.success("Account deleted");
        setIsAuth(false);
        localStorage.removeItem("token");
        window.location.replace("/login");
      })
      .catch(() => toast.error("Failed to delete account"));
  };

  const getUserInitials = () => {
    if (!profile?.name) return "?";
    return profile.name
      .split(" ")
      .map((n) => n[0])
      .join("")
      .toUpperCase()
      .substring(0, 2);
  };

  if (loading) {
    return (
      <div className={styles.loadingContainer}>
        <div className={styles.spinner}></div>
      </div>
    );
  }

  if (error) {
    return (
      <div className={styles.errorContainer}>
        <div className={styles.errorCard}>
          <span className={styles.errorIcon}>⚠️</span>
          <h3>Error loading profile</h3>
          <p>{error}</p>
          <button
            onClick={() => window.location.reload()}
            className={styles.retryButton}
          >
            Retry
          </button>
        </div>
      </div>
    );
  }

  return (
    <div className={styles.container}>
      <div className={styles.profileCard}>
        <div className={styles.profileHeader}>
          <div className={styles.avatarLarge}>{getUserInitials()}</div>

          <div className={styles.profileTitle}>
            <h1>My Profile</h1>
            <p className={styles.memberSince}>
              Member since{" "}
              {profile?.created_at
                ? new Date(profile.created_at).toLocaleDateString()
                : "Unknown"}
            </p>
          </div>

          {profile?.role && (
            <span className={styles.roleBadge}>{profile.role}</span>
          )}
        </div>

        <div className={styles.infoGrid}>
          <div className={styles.infoItem}>
            <span className={styles.infoLabel}>Email</span>
            <span className={styles.infoValue}>
              {profile?.email || "—"}
            </span>
          </div>

          <div className={styles.infoItem}>
            <span className={styles.infoLabel}>Account ID</span>
            <span className={styles.infoValue}>
              #{profile?.id || "—"}
            </span>
          </div>
        </div>

        <div className={styles.editSection}>
          <h2 className={styles.sectionTitle}>✏️ Edit Profile</h2>

          <form onSubmit={handleUpdate} autoComplete="off">
            <div className={styles.formGroup}>
              <label>Email</label>
              <input
                className={styles.input}
                name="email"
                type="email"
                value={formData.email}
                onChange={handleChange}
                required
              />
            </div>

            <div className={styles.formRow}>
              <div className={styles.formGroup}>
                <label>New Password</label>
                <input
                  className={styles.input}
                  name="password"
                  type="password"
                  value={formData.password}
                  onChange={handleChange}
                />
              </div>

              <div className={styles.formGroup}>
                <label>Confirm Password</label>
                <input
                  className={styles.input}
                  name="password_confirmation"
                  type="password"
                  value={formData.password_confirmation}
                  onChange={handleChange}
                />
              </div>
            </div>

            <div className={styles.formActions}>
              <button
                className={styles.updateButton}
                disabled={updating}
              >
                {updating ? "Updating..." : "Update Profile"}
              </button>
            </div>
          </form>
        </div>

        <div className={styles.dangerZone}>
          <h2 className={styles.sectionTitle}>⚠️ Danger Zone</h2>
          <p className={styles.dangerDescription}>
            Once you delete your account, there is no going back.
          </p>
          <button
            className={styles.deleteButton}
            onClick={handleDelete}
          >
            🗑️ Delete Account
          </button>
        </div>
      </div>
    </div>
  );
};

export default Profile;