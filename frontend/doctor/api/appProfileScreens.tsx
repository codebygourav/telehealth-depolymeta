import api from "@/lib/axios";

export interface FaqItem {
    title: string;
    description: string;
    icon: string;
}

export interface AppProfileScreensResponse {
    faq: FaqItem[];
    about_us: string;
    term_and_conditions: string;
    privacy_policy: string;
}

export const getAppProfileScreens = async (
    token?: string,
): Promise<AppProfileScreensResponse> => {
    const config = token
        ? {
            headers: {
                Authorization: `Bearer ${token}`,
            },
        }
        : {};

    const { data } = await api.get(`/app-profile-screens`, config);
    return data.data;
};