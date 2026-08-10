/**
 * The identity a member list renders: who they are, and the face MemberAvatar draws for them.
 * The avatar is nullable because the initials are the fallback rather than a placeholder image.
 */
export interface IMemberSummary {
    id: number;
    name: string;
    avatar: string | null;
}

export interface IUser {
    id: number;
    github_nickname: string;
    name: string;
    avatar: string;
    is_vehikl_member: boolean;
    email?: string;
}
