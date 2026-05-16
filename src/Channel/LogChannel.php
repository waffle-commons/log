<?php

declare(strict_types=1);

namespace Waffle\Commons\Log\Channel;

/**
 * Standardized log channels for the Waffle Ecosystem.
 * Using constants prevents typos ('securit' vs 'security') and enforces consistency.
 */
final class LogChannel
{
    /**
     * Userland application logic (Controllers, Domain Services).
     */
    public const string APP = 'app';

    /**
     * Framework internals (Kernel, Container, Boot process).
     */
    public const string CORE = 'core';

    /**
     * HTTP Layer (Requests, Responses, Routing errors).
     */
    public const string HTTP = 'http';

    /**
     * Security Layer (Authentication, Authorization, Firewall).
     */
    public const string SECURITY = 'sec';

    /**
     * Compliance & Audit Trail (Critical business actions).
     */
    public const string AUDIT = 'audit';
}
