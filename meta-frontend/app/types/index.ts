/**
 * Types for authenticated user from useSanctumAuth
 */

export interface User {
  user_id: number
  role: 'student' | 'mentor' | 'admin' | 'moderator' | string
  is_activated: number | boolean
}
