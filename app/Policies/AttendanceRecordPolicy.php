<?php

namespace App\Policies;

use App\Models\AttendanceRecord;
use App\Models\TeachingAssignment;
use App\Models\User;
use App\Support\SchoolPermissions as P;

class AttendanceRecordPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canAny([P::ATTENDANCE_VIEW_ALL, P::ATTENDANCE_VIEW_ASSIGNED]);
    }

    public function view(User $user, AttendanceRecord $attendanceRecord): bool
    {
        if ($user->can(P::ATTENDANCE_VIEW_ALL)) {
            return true;
        }

        return $user->can(P::ATTENDANCE_VIEW_ASSIGNED)
            && $user->isProfessor()
            && $this->hasMatchingAssignment($user, $attendanceRecord);
    }

    public function viewForAssignment(User $user, TeachingAssignment $teachingAssignment): bool
    {
        return $user->can(P::ATTENDANCE_VIEW_ALL)
            || ($user->can(P::ATTENDANCE_VIEW_ASSIGNED)
                && $user->isProfessor()
                && $teachingAssignment->professor_id === $user->id);
    }

    public function createForAssignment(User $user, TeachingAssignment $teachingAssignment): bool
    {
        return $user->can(P::ATTENDANCE_MANAGE_ALL)
            || ($user->can(P::ATTENDANCE_MANAGE_ASSIGNED)
                && $user->isProfessor()
                && $teachingAssignment->professor_id === $user->id);
    }

    public function update(User $user, AttendanceRecord $attendanceRecord): bool
    {
        if ($user->can(P::ATTENDANCE_MANAGE_ALL)) {
            return true;
        }

        return $user->can(P::ATTENDANCE_MANAGE_ASSIGNED)
            && $user->isProfessor()
            && $this->hasMatchingAssignment($user, $attendanceRecord);
    }

    public function delete(User $user, AttendanceRecord $attendanceRecord): bool
    {
        return $this->update($user, $attendanceRecord);
    }

    private function hasMatchingAssignment(User $user, AttendanceRecord $attendanceRecord): bool
    {
        $enrollment = $attendanceRecord->studentEnrollment;

        return $enrollment !== null
            && TeachingAssignment::query()
                ->where('professor_id', $user->id)
                ->where('classroom_id', $enrollment->classroom_id)
                ->where('academic_year_id', $enrollment->academic_year_id)
                ->exists();
    }
}
