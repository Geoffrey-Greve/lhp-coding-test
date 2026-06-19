export interface EventImage {
    url: string;
    path: string;
}

export interface EventSchedule {
    event: string;
    local: string;
    timezone: string;
    iso: string | null;
}

export interface VisualEvent {
    id: string;
    type: string;
    status: string;
    title: string;
    description: string;
    location: string;
    city_slug: string | null;
    venue: string | null;
    latitude: number | null;
    longitude: number | null;
    starts_at: number | null;
    ends_at: number | null;
    schedule: EventSchedule;
    images: EventImage[];
    attendee_count?: number;
}

export interface CityOption {
    slug: string;
    name: string;
}

export interface VisualFilters {
    from: string;
    to: string;
    city: string;
}

export interface AttendeePreview {
    id: number;
    name: string;
    email: string;
    registered_at: string | null;
}
