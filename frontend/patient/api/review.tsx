import api from "@/lib/axios";

export const fetchMyReviews = async (page = 1, per_page = 1) => {
    const { data } = await api.get(`/reviews/my?per_page=${per_page}&page=${page}`);
    return data;
};

export const submitReview = async (payload: {
  doctor_id: string;
  title: string;
  content: string;
  rating: string;
  appointment_id: string;
}) => {
  const formData = new FormData();

  formData.append("doctor_id", payload.doctor_id);
  formData.append("title", payload.title);
  formData.append("content", payload.content);
  formData.append("rating", payload.rating);
  formData.append("appointment_id", payload.appointment_id);

  const { data } = await api.post("/reviews", formData, {
    headers: {
      "Content-Type": "multipart/form-data",
    },
  });

  return data;
};