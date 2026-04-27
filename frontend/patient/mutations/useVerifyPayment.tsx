import axios from "@/lib/axios"; // your axios instance
import { useMutation } from "@tanstack/react-query";

interface VerifyPaymentPayload {
    razorpay_order_id: string;
    razorpay_payment_id: string;
    appointment_id: string;
    razorpay_signature: string
}

export const useVerifyPayment = () => {
    return useMutation({
        mutationFn: async (payload: VerifyPaymentPayload) => {
            const formData = new FormData();

            formData.append("razorpay_order_id", payload.razorpay_order_id);
            formData.append("razorpay_payment_id", payload.razorpay_payment_id);
            formData.append("razorpay_signature", payload.razorpay_signature);
            formData.append("appointment_id", payload.appointment_id);

            const response = await axios.post("/verify-payment", formData, {
                headers: {
                    "Content-Type": "multipart/form-data",
                },
            });

            return response.data;
        },
    });
};