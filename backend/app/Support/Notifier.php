<?php

namespace App\Support;

use App\Models\Application;
use App\Models\Notification;

// PRD 10.4's notification matrix, citizen-facing rows only - SMS delivery
// isn't wired up (no gateway), so these land as dashboard/in-app
// notifications. See shared/api-contract.md for what's not built yet.
class Notifier
{
    public static function applicationSubmitted(Application $application): void
    {
        self::create($application, 'APPLICATION_SUBMITTED', 'Application submitted', sprintf(
            'Your %s application (%s) has been submitted and is awaiting review.',
            self::label($application), $application->tracking_id
        ));
    }

    public static function correctionsRequested(Application $application): void
    {
        self::create($application, 'CORRECTIONS_REQUIRED', 'Corrections needed', sprintf(
            'Your application (%s) needs corrections before it can continue. Log in to view details.',
            $application->tracking_id
        ));
    }

    public static function applicationApproved(Application $application): void
    {
        self::create($application, 'APPLICATION_APPROVED', 'Application approved', sprintf(
            'Your application (%s) has been approved. Your certificate is being prepared.',
            $application->tracking_id
        ));
    }

    public static function certificateReady(Application $application): void
    {
        self::create($application, 'CERTIFICATE_READY', 'Certificate ready', sprintf(
            'Your certificate for application %s is ready.',
            $application->tracking_id
        ));
    }

    public static function applicationRejected(Application $application, string $reason): void
    {
        self::create($application, 'APPLICATION_REJECTED', 'Application rejected', sprintf(
            'Your application (%s) was rejected: %s',
            $application->tracking_id, $reason
        ));
    }

    private static function label(Application $application): string
    {
        return str_replace('_', ' ', $application->event_type);
    }

    private static function create(Application $application, string $type, string $title, string $body): void
    {
        Notification::create([
            'citizen_id' => $application->citizen_id,
            'application_id' => $application->id,
            'type' => $type,
            'title' => $title,
            'body' => $body,
        ]);
    }
}
