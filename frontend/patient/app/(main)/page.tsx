"use client";
import { Advertisements } from "@/components/pages/Dashboard/Advertisements";
import { AvailableDoctors } from "@/components/pages/Dashboard/AvailableDoctors";
import QuickLinks from "@/components/pages/Dashboard/QuickLinks";
import { TestimonialsCarousel } from "@/components/pages/Dashboard/TestimonialsCarousel";
import { UpcomingAppointments } from "@/components/pages/Dashboard/UpcomingAppointments";
import { Button } from "@/components/ui/button";
import { useAuth } from "@/context/userContext";
import { usePatientHome } from "@/queries/usePatientHome";
import { MappedAppointment } from "@/types/home";
import { Loader2, Plus } from "lucide-react";
import { useState } from "react";
import { useRouter } from "next/navigation";
import { AppSidebar } from "@/components/app-sidebar";

type Page = string;

export default function Home() {
	const { user } = useAuth();
	const [page, setPage] = useState<Page | null>(null);
	const { data, isLoading, isError } = usePatientHome();
	const homeData = data?.data;
	const router = useRouter();

	// console.log("data : ", data);
	// Map API upcoming_appointments → MappedAppointment shape for UpcomingAppointments component
	const appointments: MappedAppointment[] = (
		homeData?.upcoming_appointments ?? []
	).map((appt) => ({
		id: appt.appointment_id,
		doctorId: appt.doctor.user_id,
		doctorName: appt.doctor.name,
		doctorImage: appt.doctor.avatar,
		date: appt.appointment_date_formatted,
		time: appt.appointment_time_formatted,
		type: appt.consultation_type,
		typeLabel: appt.consultation_type_label,
		joinUrl: appt.video_consultation?.join_url,
		doctor: {
			specialty: appt.doctor.department,
			experience: appt.doctor.years_experience,
			languages: appt.doctor.languages_known,
		},

	}));
	// Map API advertisements → Advertisement shape
	const advertisements = (homeData?.advertisements ?? []).map((ad) => ({
		id: ad.id,
		title: ad.title,
		description: ad.description,
		image: ad.image,
		link: ad.link,
	}));
	// Map API patient_reviews → Testimonial shape for TestimonialsCarousel
	const testimonials = (homeData?.patient_reviews ?? []).map((r) => ({
		id: r.id,
		name: r.patient_name,
		location: r.patient_location,
		patientImage: r.patient_image,
		rating: r.rating,
		subject: r.title,
		feedback: r.content,
		reviewCount: r.total_reviews,
		doctorName: r.doctor_name,
		doctorImage: r.doctor_avatar,
		time: r.created_at,
	}));
	if (isLoading) {
		return (
			<div className="flex items-center justify-center min-h-[60vh]">
				<Loader2 className="w-8 h-8 animate-spin text-primary" />
			</div>
		);
	}
	if (isError || !homeData) {
		return (
			<div className="flex items-center justify-center min-h-[60vh]">
				<p className="text-destructive font-semibold">
					Failed to load dashboard. Please try again.
				</p>
			</div>
		);
	}
	return (
		<div className="space-y-5">
			<header className="w-full rounded bg-white shadow-card-lg p-5 flex flex-col md:flex-row md:items-center justify-between gap-6">

				{/* Left Content */}
				<div>
					<h1 className="text-[24px]  font-bold tracking-tight text-primary">
						Welcome back{user?.first_name || user?.last_name ? "," : ""}{" "}
						<span className="text-primary">
							{user?.first_name ?? ""} {user?.last_name ?? ""}
						</span>
					</h1>

					<p className="text-muted-foreground text-base">
						Your health summary is looking stable today.
					</p>
				</div>

				{/* Right Action */}
				<Button
					onClick={() => {
						window.open("/find-doctors", "_blank");
						setPage && setPage("/find-doctors");
					}}
					className="flex items-center justify-center gap-2 px-3 font-semibold py-4.5 text-sm md:text-xs rounded-[5px] bg-primary text-white hover:bg-primary/90 shadow-md transition-all"
				>
					<Plus className="w-4 h-4eretrtt" />
					Book Appointment
				</Button>

			</header>
			<div className="flex flex-col lg:flex-row gap-5 items-stretch">

				{/* Left - 40% */}
				<div className="w-full lg:basis-[45%]">
					<UpcomingAppointments
						appointments={appointments}
						onViewAll={() => setPage && setPage("my-appointments")}
						onStartCall={(id) => setPage && setPage("live-session")}
						onBookFirst={() => setPage && setPage("find-doctor")}
					/>
				</div>

				{/* Right - 60% */}
				<div className="w-full lg:basis-[55%]">
					<QuickLinks
						reportSummary="Blood Work Result: Normal"
						prescriptionSummary="2 Active medications • Refill ready"
						onViewReports={() => router.push("/reviews")}
						onManageRefills={() => router.push("/transactions")}
					/>
				</div>

			</div>
			
			<AvailableDoctors
				doctors={homeData.available_doctors}
				onBookNow={(doctorId: string) => {
					setPage && setPage("live-session");
				}}
				
				onShowAll={() => router.push("/find-doctors")}
			/>
			{/* Advertisements */}
			<Advertisements ads={advertisements} />

			{/* Testimonials */}
			<TestimonialsCarousel testimonials={testimonials} />

		</div>
	);
}
