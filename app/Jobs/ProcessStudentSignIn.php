<?php

namespace App\Jobs;

use App\Events\AttendanceUpdated;
use App\Models\AttendanceEntry;
use App\Models\ClassModel;
use App\Models\Student;
use App\Services\BrevoEmailService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;

class ProcessStudentSignIn implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 60;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $classId,
        public string $studentEmail,
        public string $studentName,
        public float $studentLat,
        public float $studentLon,
        public float $distance,
        public bool $isLate,
        public string $currentDateTime, // Store as string for queue serialization
        public ?int $studentId = null
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('🚀 ProcessStudentSignIn job started', [
            'class_id' => $this->classId,
            'student_email' => $this->studentEmail,
            'student_name' => $this->studentName,
            'student_id' => $this->studentId,
            'timestamp' => $this->currentDateTime
        ]);
        
        try {
            $class = ClassModel::findOrFail($this->classId);
            $currentDateTime = Carbon::parse($this->currentDateTime);
            $todayDate = $currentDateTime->format('Y-m-d');
            $geofenceRadius = $class->geofence_radius ?? env('GEOFENCE_RADIUS', 500);
            
            Log::info('📚 Class found for sign-in processing', [
                'class_id' => $class->id,
                'class_code' => $class->class_code,
                'class_name' => $class->class_name
            ]);

            // Store attendance record in attendance_entries table
            $attendanceRecord = AttendanceEntry::create([
                'class_id' => $class->id,
                'student_id' => $this->studentId,
                'class_code' => $class->class_code,
                'class_name' => $class->class_name,
                'teacher_email' => $class->teacher_email,
                'student_email' => $this->studentEmail,
                'student_name' => $this->studentName,
                'date' => $todayDate,
                'sign_in_time' => $currentDateTime->format('H:i:s'),
                'status' => $this->isLate ? 'Late' : 'On Time',
                'distance' => round($this->distance, 2),
                'student_latitude' => (float)$this->studentLat,
                'student_longitude' => (float)$this->studentLon,
                'timestamp' => $currentDateTime,
                'geofence_entry_time' => $currentDateTime,
                'currently_inside' => true,
                'time_inside_geofence' => 0,
                'time_outside_geofence' => 0,
                'last_location_update' => $currentDateTime,
            ]);

            Log::info('✅ Attendance record created via queue', [
                'id' => $attendanceRecord->id,
                'student' => $this->studentName,
                'class' => $class->class_code
            ]);

            // Send email notification to guardian (if email address exists)
            $emailSent = false;
            $guardianEmail = null;
            $guardianName = null;

            // Get student's guardian information
            // Try by ID first, then fallback to email lookup
            $student = null;
            
            Log::info('🔍 Looking up student for guardian email', [
                'student_id' => $this->studentId,
                'student_email' => $this->studentEmail
            ]);
            
            if ($this->studentId) {
                $student = Student::find($this->studentId);
                Log::info('🔍 Student lookup by ID result', [
                    'student_id' => $this->studentId,
                    'found' => $student ? 'yes' : 'no',
                    'guardian_email' => $student ? ($student->guardian_email ?? 'null') : 'N/A'
                ]);
            }
            
            // If not found by ID, try by email
            if (!$student && $this->studentEmail) {
                Log::info('🔍 Student not found by ID, trying email lookup', [
                    'student_email' => $this->studentEmail
                ]);
                $student = Student::where('email', $this->studentEmail)->first();
                Log::info('🔍 Student lookup by email result', [
                    'student_email' => $this->studentEmail,
                    'found' => $student ? 'yes' : 'no',
                    'guardian_email' => $student ? ($student->guardian_email ?? 'null') : 'N/A',
                    'guardian_name' => $student ? ($student->guardian_name ?? 'null') : 'N/A'
                ]);
            }
            
            // Get guardian info if student found
            if ($student && $student->guardian_email) {
                $guardianEmail = $student->guardian_email;
                $guardianName = $student->guardian_name ?? 'Guardian';
                
                Log::info('✅ Guardian email found for student', [
                    'student_email' => $this->studentEmail,
                    'student_id' => $student->id,
                    'guardian_email' => $guardianEmail,
                    'guardian_name' => $guardianName
                ]);
            } else {
                Log::warning('⚠️ No guardian email found for student', [
                    'student_email' => $this->studentEmail,
                    'student_id' => $this->studentId,
                    'student_found' => $student ? 'yes' : 'no',
                    'has_guardian_email' => $student && $student->guardian_email ? 'yes' : 'no',
                    'student_data' => $student ? [
                        'id' => $student->id,
                        'email' => $student->email,
                        'guardian_email' => $student->guardian_email,
                        'guardian_name' => $student->guardian_name
                    ] : 'student_not_found'
                ]);
            }

            if ($guardianEmail) {
                try {
                    // Prepare email data
                    $emailData = [
                        'studentName' => $this->studentName,
                        'guardianName' => $guardianName,
                        'className' => $class->class_name,
                        'signInTime' => $currentDateTime->format('g:i A'),
                        'signInDate' => $currentDateTime->format('l, F j, Y'),
                        'status' => $this->isLate ? 'Late' : 'On Time',
                        'distance' => round($this->distance, 2),
                        'isWithinGeofence' => $this->distance <= $geofenceRadius,
                        'teacherName' => $class->teacher_name ?? 'Teacher',
                    ];

                    // Render email HTML
                    $htmlContent = View::make('emails.student-signin', $emailData)->render();
                    
                    // Prepare subject
                    $subject = '[PinPoint] ' . $this->studentName . ' signed in to ' . $class->class_name;

                    // Send email using Brevo API
                    $brevoService = new BrevoEmailService();
                    $result = $brevoService->sendEmail($guardianEmail, $subject, $htmlContent);
                    
                    if ($result['success']) {
                        $emailSent = true;
                        Log::info('✅ Email sent to guardian via Brevo', [
                            'guardian_email' => $guardianEmail,
                            'student' => $this->studentName,
                            'class' => $class->class_code,
                            'message_id' => $result['message_id'] ?? 'unknown'
                        ]);
                    } else {
                        Log::warning('⚠️ Email sending failed via Brevo', [
                            'guardian_email' => $guardianEmail,
                            'error' => $result['message'] ?? 'Unknown error'
                        ]);
                    }
                } catch (\Exception $emailError) {
                    Log::error('❌ Email failed to send via Brevo', [
                        'email' => $guardianEmail,
                        'error' => $emailError->getMessage(),
                        'trace' => $emailError->getTraceAsString()
                    ]);
                    // Don't fail the job if email fails - attendance is already recorded
                }
            } else {
                Log::info('ℹ️ No guardian email found for student', [
                    'student_id' => $this->studentId,
                    'student_email' => $this->studentEmail
                ]);
            }

            // Broadcast attendance update event
            $attendanceData = [
                'id' => $attendanceRecord->id,
                'student_name' => $this->studentName,
                'student_email' => $this->studentEmail,
                'sign_in_time' => $currentDateTime->format('H:i:s'),
                'status' => $this->isLate ? 'Late' : 'On Time',
                'distance' => round($this->distance, 2),
                'latitude' => (float)$this->studentLat,
                'longitude' => (float)$this->studentLon,
                'timestamp' => $currentDateTime->toIso8601String(),
            ];
            broadcast(new AttendanceUpdated($this->classId, $attendanceData));

        } catch (\Exception $e) {
            Log::error('❌ Failed to process student sign-in in queue', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'class_id' => $this->classId,
                'student' => $this->studentEmail
            ]);
            throw $e; // Re-throw to trigger retry
        }
    }
}
