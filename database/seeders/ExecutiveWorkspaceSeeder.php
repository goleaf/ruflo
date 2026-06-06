<?php

namespace Database\Seeders;

use App\Data\Todos\SavedTodoViewData;
use App\Enums\AutomationRuleKind;
use App\Enums\AutomationRunStatus;
use App\Enums\HabitFrequency;
use App\Enums\PomodoroSessionStatus;
use App\Enums\Priority;
use App\Enums\ReminderStatus;
use App\Enums\TaskTemplateKind;
use App\Enums\TimeEntrySource;
use App\Enums\TimeEntryStatus;
use App\Models\AutomationRule;
use App\Models\AutomationRuleRun;
use App\Models\Goal;
use App\Models\GoalMilestone;
use App\Models\Habit;
use App\Models\HabitCheckIn;
use App\Models\PomodoroSession;
use App\Models\Project;
use App\Models\Reminder;
use App\Models\SavedTodoView;
use App\Models\Tag;
use App\Models\TimeEntry;
use App\Models\Todo;
use App\Models\TodoChecklistItem;
use App\Models\TodoDependency;
use App\Models\TodoTemplate;
use App\Models\User;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ExecutiveWorkspaceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (! $this->canSeedDemoUsers()) {
            return;
        }

        $personas = $this->personas();
        $users = User::query()
            ->whereIn('email', collect($personas)->pluck('email')->all())
            ->get()
            ->keyBy('email');

        DB::transaction(function () use ($personas, $users): void {
            foreach ($personas as $persona) {
                $user = $users->get($persona['email']);

                if (! $user instanceof User) {
                    continue;
                }

                $this->seedPersona($user, $persona);
            }
        });
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function personas(): array
    {
        return [
            $this->buildPersona(
                email: (string) config('demo.login_panel.users.0.email', 'test@example.com'),
                company: 'Apple',
                role: 'Manager of Apple company',
                terms: [
                    'strategy_project' => 'Executive Operations',
                    'launch_project' => 'Product Launch Calendar',
                    'field_project' => 'Retail Excellence',
                    'trust_project' => 'Privacy and Platform Trust',
                    'archive_project' => 'Supplier Readiness Archive',
                    'customer_project' => 'Customer Experience War Room',
                    'people_project' => 'Leadership Talent Bench',
                    'finance_project' => 'Finance and Board Governance',
                    'platform_project' => 'Apple Intelligence Platform Bets',
                    'partner_project' => 'Channel and Developer Ecosystem',
                    'communications_project' => 'Executive Communications',
                    'recruiting_project' => 'Hiring and Org Design',
                    'incident_project' => 'Incident and Customer Recovery',
                    'analytics_project' => 'Metrics and Insights',
                    'vendor_project' => 'Vendor and Supplier Governance',
                    'launch_focus' => 'product launch readiness',
                    'field_focus' => 'global retail service scale',
                    'trust_focus' => 'privacy trust evidence',
                    'customer_focus' => 'customer experience signal',
                    'people_focus' => 'leadership talent bench',
                    'finance_focus' => 'finance and board governance',
                    'platform_focus' => 'Apple Intelligence platform bet',
                    'partner_focus' => 'developer and channel ecosystem',
                    'communications_focus' => 'executive communications narrative',
                    'recruiting_focus' => 'manager hiring and org design',
                    'incident_focus' => 'customer recovery and incident response',
                    'analytics_focus' => 'executive metrics and insight system',
                    'vendor_focus' => 'supplier and vendor governance',
                    'capacity_focus' => 'supplier capacity',
                    'readout_focus' => 'product readout',
                    'evidence_focus' => 'customer evidence',
                    'morning_habit' => 'morning executive brief',
                    'risk_habit' => 'launch risk scan',
                    'review_habit' => 'manager weekly operating review',
                    'customer_habit' => 'customer signal review',
                    'finance_habit' => 'finance guardrail review',
                    'people_habit' => 'leadership bench review',
                    'platform_habit' => 'platform bet review',
                    'partner_habit' => 'developer ecosystem review',
                    'communications_habit' => 'communications narrative review',
                    'recruiting_habit' => 'hiring pipeline review',
                    'incident_habit' => 'incident recovery review',
                    'analytics_habit' => 'metrics quality review',
                ],
            ),
            $this->buildPersona(
                email: (string) config('demo.login_panel.users.1.email', 'second@example.com'),
                company: 'Microsoft',
                role: 'Top manager of Microsoft company',
                terms: [
                    'strategy_project' => 'Cloud Leadership',
                    'launch_project' => 'Copilot Launch Calendar',
                    'field_project' => 'Partner Field Operations',
                    'trust_project' => 'Enterprise Security Trust',
                    'archive_project' => 'FY25 Executive Archive',
                    'customer_project' => 'Enterprise Customer Signal Room',
                    'people_project' => 'Leadership Talent Bench',
                    'finance_project' => 'Finance and Board Governance',
                    'platform_project' => 'AI Platform Future Bets',
                    'partner_project' => 'Partner and Developer Ecosystem',
                    'communications_project' => 'Executive Communications',
                    'recruiting_project' => 'Leadership Hiring and Org Design',
                    'incident_project' => 'Incident and Customer Recovery',
                    'analytics_project' => 'Metrics and Insights',
                    'vendor_project' => 'Vendor and Capacity Governance',
                    'launch_focus' => 'Copilot enterprise rollout',
                    'field_focus' => 'partner field scale',
                    'trust_focus' => 'enterprise security evidence',
                    'customer_focus' => 'enterprise customer signal',
                    'people_focus' => 'leadership talent bench',
                    'finance_focus' => 'finance and board governance',
                    'platform_focus' => 'AI platform future bet',
                    'partner_focus' => 'partner and developer ecosystem',
                    'communications_focus' => 'executive communications narrative',
                    'recruiting_focus' => 'leadership hiring and org design',
                    'incident_focus' => 'customer recovery and incident response',
                    'analytics_focus' => 'executive metrics and insight system',
                    'vendor_focus' => 'vendor and capacity governance',
                    'capacity_focus' => 'Azure capacity',
                    'readout_focus' => 'Copilot readout',
                    'evidence_focus' => 'customer evidence',
                    'morning_habit' => 'top manager morning brief',
                    'risk_habit' => 'Copilot risk scan',
                    'review_habit' => 'weekly leadership operating review',
                    'customer_habit' => 'enterprise customer signal review',
                    'finance_habit' => 'finance guardrail review',
                    'people_habit' => 'leadership bench review',
                    'platform_habit' => 'AI platform bet review',
                    'partner_habit' => 'partner ecosystem review',
                    'communications_habit' => 'communications narrative review',
                    'recruiting_habit' => 'hiring pipeline review',
                    'incident_habit' => 'incident recovery review',
                    'analytics_habit' => 'metrics quality review',
                ],
            ),
        ];
    }

    /**
     * @param  array<string, string>  $terms
     * @return array<string, mixed>
     */
    private function buildPersona(string $email, string $company, string $role, array $terms): array
    {
        $slug = str($company)->lower()->slug()->toString();

        return [
            'email' => $email,
            'company' => $company,
            'role' => $role,
            'projects' => [
                'strategy' => ['name' => "{$company} {$terms['strategy_project']}", 'color' => 'blue'],
                'launch' => ['name' => "{$company} {$terms['launch_project']}", 'color' => 'purple'],
                'field' => ['name' => "{$company} {$terms['field_project']}", 'color' => 'emerald'],
                'trust' => ['name' => "{$company} {$terms['trust_project']}", 'color' => 'red'],
                'customer' => ['name' => "{$company} {$terms['customer_project']}", 'color' => 'amber'],
                'people' => ['name' => "{$company} {$terms['people_project']}", 'color' => 'pink'],
                'finance' => ['name' => "{$company} {$terms['finance_project']}", 'color' => 'green'],
                'platform' => ['name' => "{$company} {$terms['platform_project']}", 'color' => 'cyan'],
                'partner' => ['name' => "{$company} {$terms['partner_project']}", 'color' => 'indigo'],
                'communications' => ['name' => "{$company} {$terms['communications_project']}", 'color' => 'rose'],
                'recruiting' => ['name' => "{$company} {$terms['recruiting_project']}", 'color' => 'lime'],
                'incident' => ['name' => "{$company} {$terms['incident_project']}", 'color' => 'orange'],
                'analytics' => ['name' => "{$company} {$terms['analytics_project']}", 'color' => 'sky'],
                'vendor' => ['name' => "{$company} {$terms['vendor_project']}", 'color' => 'slate'],
                'archive' => ['name' => "{$company} {$terms['archive_project']}", 'color' => 'zinc', 'archived' => true],
            ],
            'tags' => [
                'board' => ['name' => "{$slug}-board-review", 'color' => 'blue'],
                'launch' => ['name' => "{$slug}-launch", 'color' => 'purple'],
                'finance' => ['name' => "{$slug}-finance", 'color' => 'green'],
                'legal' => ['name' => "{$slug}-trust", 'color' => 'red'],
                'people' => ['name' => "{$slug}-people", 'color' => 'pink'],
                'blocker' => ['name' => "{$slug}-blocker", 'color' => 'amber'],
                'customer' => ['name' => "{$slug}-customer-signal", 'color' => 'orange'],
                'ai' => ['name' => "{$slug}-ai-platform", 'color' => 'cyan'],
                'security' => ['name' => "{$slug}-security-review", 'color' => 'red'],
                'governance' => ['name' => "{$slug}-governance", 'color' => 'zinc'],
                'ops' => ['name' => "{$slug}-ops-cadence", 'color' => 'emerald'],
                'partner' => ['name' => "{$slug}-partner-ecosystem", 'color' => 'indigo'],
                'communications' => ['name' => "{$slug}-executive-comms", 'color' => 'rose'],
                'recruiting' => ['name' => "{$slug}-recruiting", 'color' => 'lime'],
                'incident' => ['name' => "{$slug}-incident-review", 'color' => 'orange'],
                'metrics' => ['name' => "{$slug}-metrics", 'color' => 'sky'],
                'vendor' => ['name' => "{$slug}-vendor", 'color' => 'slate'],
                'accessibility' => ['name' => "{$slug}-accessibility", 'color' => 'violet'],
            ],
            'goals' => $this->goals($company, $terms),
            'habits' => $this->habits($company, $terms),
            'tasks' => $this->tasks($company, $terms),
            'dependencies' => [
                ['todo' => 'operating_review', 'blocker' => 'strategy_narrative'],
                ['todo' => 'capacity_plan', 'blocker' => 'trust_gate'],
                ['todo' => 'field_briefing', 'blocker' => 'customer_evidence'],
                ['todo' => 'risk_register', 'blocker' => 'overdue_partner'],
                ['todo' => 'finance_guardrails', 'blocker' => 'investment_memo'],
                ['todo' => 'customer_signal_readout', 'blocker' => 'customer_evidence'],
                ['todo' => 'platform_bet_review', 'blocker' => 'trust_gate'],
                ['todo' => 'people_successor_plan', 'blocker' => 'executive_offsite_agenda'],
                ['todo' => 'security_exception_review', 'blocker' => 'ai_governance_signoff'],
                ['todo' => 'support_capacity_readout', 'blocker' => 'field_briefing'],
                ['todo' => 'partner_scorecard', 'blocker' => 'field_briefing'],
                ['todo' => 'developer_council_agenda', 'blocker' => 'partner_scorecard'],
                ['todo' => 'executive_comms_memo', 'blocker' => 'board_prebrief'],
                ['todo' => 'press_qna_packet', 'blocker' => 'executive_comms_memo'],
                ['todo' => 'recruiting_calibration', 'blocker' => 'hiring_capacity_review'],
                ['todo' => 'manager_load_review', 'blocker' => 'people_successor_plan'],
                ['todo' => 'incident_recovery_plan', 'blocker' => 'security_exception_review'],
                ['todo' => 'customer_apology_review', 'blocker' => 'incident_recovery_plan'],
                ['todo' => 'metrics_health_review', 'blocker' => 'data_quality_readout'],
                ['todo' => 'forecast_dashboard_review', 'blocker' => 'annual_operating_plan'],
                ['todo' => 'vendor_contract_review', 'blocker' => 'finance_guardrails'],
                ['todo' => 'supplier_capacity_escalation', 'blocker' => 'capacity_plan'],
                ['todo' => 'quarterly_operating_followup', 'blocker' => 'operating_review'],
            ],
            'reminders' => [
                ['todo' => 'strategy_narrative', 'days' => 2, 'status' => ReminderStatus::Pending],
                ['todo' => 'operating_review', 'days' => 7, 'status' => ReminderStatus::Pending],
                ['todo' => 'trust_gate', 'days' => 10, 'status' => ReminderStatus::Pending],
                ['todo' => 'capacity_plan', 'days' => 14, 'status' => ReminderStatus::Pending],
                ['todo' => 'overdue_partner', 'minutes' => -40, 'status' => ReminderStatus::Pending],
                ['todo' => 'completed_sync', 'minutes' => -90, 'status' => ReminderStatus::Processed],
                ['todo' => 'archived_packet', 'minutes' => -120, 'status' => ReminderStatus::Skipped, 'skipped_reason' => 'task_archived'],
                ['todo' => 'deleted_duplicate', 'minutes' => -180, 'status' => ReminderStatus::Skipped, 'skipped_reason' => 'task_trashed'],
                ['todo' => 'finance_guardrails', 'days' => 20, 'status' => ReminderStatus::Pending],
                ['todo' => 'annual_operating_plan', 'days' => 60, 'status' => ReminderStatus::Pending],
                ['todo' => 'board_prebrief', 'days' => 3, 'status' => ReminderStatus::Pending],
                ['todo' => 'customer_signal_readout', 'days' => 24, 'status' => ReminderStatus::Pending],
                ['todo' => 'support_capacity_readout', 'days' => 32, 'status' => ReminderStatus::Pending],
                ['todo' => 'people_successor_plan', 'days' => 42, 'status' => ReminderStatus::Pending],
                ['todo' => 'platform_bet_review', 'days' => 35, 'status' => ReminderStatus::Pending],
                ['todo' => 'security_exception_review', 'days' => 16, 'status' => ReminderStatus::Pending],
                ['todo' => 'completed_customer_sync', 'minutes' => -180, 'status' => ReminderStatus::Processed],
                ['todo' => 'archived_platform_note', 'minutes' => -210, 'status' => ReminderStatus::Skipped, 'skipped_reason' => 'task_archived'],
                ['todo' => 'partner_scorecard', 'days' => 26, 'status' => ReminderStatus::Pending],
                ['todo' => 'developer_council_agenda', 'days' => 33, 'status' => ReminderStatus::Pending],
                ['todo' => 'executive_comms_memo', 'days' => 11, 'status' => ReminderStatus::Pending],
                ['todo' => 'press_qna_packet', 'days' => 13, 'status' => ReminderStatus::Pending],
                ['todo' => 'recruiting_calibration', 'days' => 46, 'status' => ReminderStatus::Pending],
                ['todo' => 'manager_load_review', 'days' => 54, 'status' => ReminderStatus::Pending],
                ['todo' => 'incident_recovery_plan', 'days' => 6, 'status' => ReminderStatus::Pending],
                ['todo' => 'customer_apology_review', 'days' => 8, 'status' => ReminderStatus::Pending],
                ['todo' => 'metrics_health_review', 'days' => 27, 'status' => ReminderStatus::Pending],
                ['todo' => 'forecast_dashboard_review', 'days' => 58, 'status' => ReminderStatus::Pending],
                ['todo' => 'vendor_contract_review', 'days' => 72, 'status' => ReminderStatus::Pending],
                ['todo' => 'supplier_capacity_escalation', 'minutes' => -65, 'status' => ReminderStatus::Pending],
                ['todo' => 'completed_partner_review', 'minutes' => -240, 'status' => ReminderStatus::Processed],
                ['todo' => 'archived_incident_postmortem', 'minutes' => -260, 'status' => ReminderStatus::Skipped, 'skipped_reason' => 'task_archived'],
                ['todo' => 'quarterly_operating_followup', 'days' => 112, 'status' => ReminderStatus::Pending],
            ],
            'pomodoros' => [
                ['todo' => 'strategy_narrative', 'status' => PomodoroSessionStatus::Running, 'duration' => 25, 'elapsed' => 420],
                ['todo' => 'operating_review', 'status' => PomodoroSessionStatus::Paused, 'duration' => 45, 'elapsed' => 1260],
                ['todo' => 'trust_gate', 'status' => PomodoroSessionStatus::Completed, 'duration' => 30, 'elapsed' => 1800],
                ['todo' => 'risk_register', 'status' => PomodoroSessionStatus::Abandoned, 'duration' => 25, 'elapsed' => 600],
                ['todo' => 'finance_guardrails', 'status' => PomodoroSessionStatus::Completed, 'duration' => 50, 'elapsed' => 3000],
                ['todo' => 'customer_signal_readout', 'status' => PomodoroSessionStatus::Paused, 'duration' => 25, 'elapsed' => 900],
                ['todo' => 'platform_bet_review', 'status' => PomodoroSessionStatus::Running, 'duration' => 30, 'elapsed' => 720],
                ['todo' => 'people_successor_plan', 'status' => PomodoroSessionStatus::Completed, 'duration' => 35, 'elapsed' => 2100],
                ['todo' => 'partner_scorecard', 'status' => PomodoroSessionStatus::Completed, 'duration' => 40, 'elapsed' => 2400],
                ['todo' => 'executive_comms_memo', 'status' => PomodoroSessionStatus::Paused, 'duration' => 30, 'elapsed' => 960],
                ['todo' => 'incident_recovery_plan', 'status' => PomodoroSessionStatus::Running, 'duration' => 25, 'elapsed' => 600],
                ['todo' => 'metrics_health_review', 'status' => PomodoroSessionStatus::Completed, 'duration' => 45, 'elapsed' => 2700],
                ['todo' => 'vendor_contract_review', 'status' => PomodoroSessionStatus::Completed, 'duration' => 35, 'elapsed' => 2100],
            ],
            'time_entries' => $this->timeEntries($company),
            'automation_runs' => $this->automationRuns($company),
        ];
    }

    /**
     * @param  array<string, string>  $terms
     * @return list<array<string, mixed>>
     */
    private function goals(string $company, array $terms): array
    {
        return [
            [
                'key' => 'operating_review',
                'title' => "{$company} FY26 executive operating review",
                'project' => 'strategy',
                'description' => "Prepare a complete {$company} executive operating package that connects launch readiness, field capacity, finance exposure, people commitments, and trust approvals.",
                'target_days' => 45,
            ],
            [
                'key' => 'launch_readiness',
                'title' => "{$company} {$terms['launch_focus']} runway",
                'project' => 'launch',
                'description' => "Coordinate product, finance, support, field, legal, and leadership workstreams so future {$company} launch dates have clear owners and escalation paths.",
                'target_days' => 75,
            ],
            [
                'key' => 'field_scale',
                'title' => "{$company} {$terms['field_focus']} plan",
                'project' => 'field',
                'description' => 'Build a manager-grade field plan for staffing, enablement, customer education, support capacity, and post-launch executive reporting.',
                'target_days' => 105,
            ],
            [
                'key' => 'trust_evidence',
                'title' => "{$company} {$terms['trust_focus']} pack",
                'project' => 'trust',
                'description' => 'Assemble security, privacy, accessibility, compliance, and customer evidence needed before executive sign-off.',
                'target_days' => 120,
            ],
            [
                'key' => 'customer_signal',
                'title' => "{$company} {$terms['customer_focus']} program",
                'project' => 'customer',
                'description' => "Turn executive-level {$company} customer feedback, support escalations, enterprise account notes, and field signals into future-dated decisions.",
                'target_days' => 135,
            ],
            [
                'key' => 'people_cadence',
                'title' => "{$company} {$terms['people_focus']} cadence",
                'project' => 'people',
                'description' => "Build a realistic leadership bench, hiring capacity, succession, offsite, and manager follow-up cadence for {$company}.",
                'target_days' => 150,
            ],
            [
                'key' => 'finance_governance',
                'title' => "{$company} {$terms['finance_focus']} package",
                'project' => 'finance',
                'description' => "Prepare board-grade investment guardrails, operating plan assumptions, financial exposure, and governance notes for {$company}.",
                'target_days' => 165,
            ],
            [
                'key' => 'platform_bets',
                'title' => "{$company} {$terms['platform_focus']} portfolio",
                'project' => 'platform',
                'description' => "Track AI, platform, security, data quality, product risk, and executive sign-off work for future {$company} bets.",
                'target_days' => 180,
            ],
            [
                'key' => 'partner_ecosystem',
                'title' => "{$company} {$terms['partner_focus']} plan",
                'project' => 'partner',
                'description' => "Build a practical {$company} partner and developer ecosystem plan with account health, enablement, co-sell, developer council, and executive escalation notes.",
                'target_days' => 195,
            ],
            [
                'key' => 'executive_communications',
                'title' => "{$company} {$terms['communications_focus']} system",
                'project' => 'communications',
                'description' => "Prepare future-ready {$company} executive communications with board narratives, customer language, press Q&A, internal notes, and risk-aware approval paths.",
                'target_days' => 210,
            ],
            [
                'key' => 'recruiting_org_design',
                'title' => "{$company} {$terms['recruiting_focus']} runway",
                'project' => 'recruiting',
                'description' => "Create a realistic {$company} management runway for hiring priorities, org design, calibration, successor coverage, and manager load balancing.",
                'target_days' => 225,
            ],
            [
                'key' => 'incident_recovery',
                'title' => "{$company} {$terms['incident_focus']} playbook",
                'project' => 'incident',
                'description' => "Track incident recovery, customer apology review, legal approvals, security lessons, support readiness, and executive postmortem actions for {$company}.",
                'target_days' => 240,
            ],
            [
                'key' => 'analytics_system',
                'title' => "{$company} {$terms['analytics_focus']}",
                'project' => 'analytics',
                'description' => "Turn {$company} operating metrics into a manager-grade system covering forecast dashboards, data quality, customer health, launch metrics, and board-ready signals.",
                'target_days' => 255,
            ],
            [
                'key' => 'vendor_governance',
                'title' => "{$company} {$terms['vendor_focus']} review",
                'project' => 'vendor',
                'description' => "Coordinate {$company} supplier and vendor governance with contract exposure, capacity escalation, accessibility evidence, renewal risk, and finance controls.",
                'target_days' => 270,
            ],
        ];
    }

    /**
     * @param  array<string, string>  $terms
     * @return list<array<string, mixed>>
     */
    private function habits(string $company, array $terms): array
    {
        return [
            [
                'key' => 'morning_brief',
                'title' => "{$company} {$terms['morning_habit']}",
                'goal' => 'operating_review',
                'description' => "Review the top {$company} launch, field, people, finance, blocker, and trust signals before opening the leadership queue.",
                'frequency' => HabitFrequency::Daily,
                'target_count' => 1,
            ],
            [
                'key' => 'risk_scan',
                'title' => "{$company} {$terms['risk_habit']}",
                'goal' => 'launch_readiness',
                'description' => "Scan ambiguous {$company} launch blockers and turn them into named owners, dates, and executive decisions.",
                'frequency' => HabitFrequency::Daily,
                'target_count' => 2,
            ],
            [
                'key' => 'weekly_review',
                'title' => "{$company} {$terms['review_habit']}",
                'goal' => 'field_scale',
                'description' => 'Close the week by reconciling commitments, customer impact, future milestones, and unresolved escalations.',
                'frequency' => HabitFrequency::Weekly,
                'target_count' => 1,
            ],
            [
                'key' => 'customer_signal_review',
                'title' => "{$company} {$terms['customer_habit']}",
                'goal' => 'customer_signal',
                'description' => "Review enterprise customer signals, support escalations, adoption concerns, and manager follow-up notes for {$company}.",
                'frequency' => HabitFrequency::Daily,
                'target_count' => 1,
            ],
            [
                'key' => 'finance_guardrail_review',
                'title' => "{$company} {$terms['finance_habit']}",
                'goal' => 'finance_governance',
                'description' => "Reconcile {$company} spend, forecast exposure, investment approvals, and board governance notes.",
                'frequency' => HabitFrequency::Weekly,
                'target_count' => 1,
            ],
            [
                'key' => 'people_bench_review',
                'title' => "{$company} {$terms['people_habit']}",
                'goal' => 'people_cadence',
                'description' => "Track successor readiness, hiring capacity, manager escalations, and leadership follow-through for {$company}.",
                'frequency' => HabitFrequency::Weekly,
                'target_count' => 2,
            ],
            [
                'key' => 'platform_bet_review',
                'title' => "{$company} {$terms['platform_habit']}",
                'goal' => 'platform_bets',
                'description' => "Review {$company} AI/platform bets, trust evidence, security exceptions, and future executive decision points.",
                'frequency' => HabitFrequency::Daily,
                'target_count' => 1,
            ],
            [
                'key' => 'partner_ecosystem_review',
                'title' => "{$company} {$terms['partner_habit']}",
                'goal' => 'partner_ecosystem',
                'description' => "Review {$company} partner health, developer council notes, channel blockers, and next executive follow-up dates.",
                'frequency' => HabitFrequency::Weekly,
                'target_count' => 1,
            ],
            [
                'key' => 'communications_review',
                'title' => "{$company} {$terms['communications_habit']}",
                'goal' => 'executive_communications',
                'description' => "Refine {$company} executive language for customer, board, press, analyst, and internal leadership contexts.",
                'frequency' => HabitFrequency::Daily,
                'target_count' => 1,
            ],
            [
                'key' => 'recruiting_pipeline_review',
                'title' => "{$company} {$terms['recruiting_habit']}",
                'goal' => 'recruiting_org_design',
                'description' => "Review {$company} hiring stages, successor coverage, manager load, and org-design risks.",
                'frequency' => HabitFrequency::Weekly,
                'target_count' => 2,
            ],
            [
                'key' => 'incident_recovery_review',
                'title' => "{$company} {$terms['incident_habit']}",
                'goal' => 'incident_recovery',
                'description' => "Review {$company} incident recovery owners, customer communication, support readiness, and executive postmortem dates.",
                'frequency' => HabitFrequency::Daily,
                'target_count' => 1,
            ],
            [
                'key' => 'metrics_quality_review',
                'title' => "{$company} {$terms['analytics_habit']}",
                'goal' => 'analytics_system',
                'description' => "Check {$company} metric definitions, forecast dashboard quality, adoption indicators, and board-ready signal integrity.",
                'frequency' => HabitFrequency::Weekly,
                'target_count' => 1,
            ],
        ];
    }

    /**
     * @param  array<string, string>  $terms
     * @return list<array<string, mixed>>
     */
    private function tasks(string $company, array $terms): array
    {
        $titles = [
            'strategy_narrative' => "Confirm {$company} board strategy narrative",
            'operating_review' => "Prepare {$company} executive operating review",
            'trust_gate' => "Review {$company} trust and legal launch gate",
            'capacity_plan' => "Approve {$company} {$terms['capacity_focus']} plan",
            'roadmap_review' => "Hold {$company} cross-functional roadmap review",
            'field_briefing' => "Plan {$company} field leadership briefing",
            'investment_memo' => "Draft {$company} Q3 investment memo",
            'blocker_review' => "Map {$company} blockers for dependency review",
            'overdue_partner' => "Escalate overdue {$company} partner decision",
            'inbox_board' => "Capture {$company} board follow-up inbox note",
            'inbox_analyst' => "Capture {$company} analyst question inbox note",
            'focus_readout' => "Block focus time for {$company} {$terms['readout_focus']}",
            'risk_register' => "Reconcile {$company} launch risk register",
            'customer_evidence' => "Confirm {$company} {$terms['evidence_focus']} pack",
            'completed_sync' => "Completed {$company} leadership sync notes",
            'completed_budget' => "Completed {$company} budget checkpoint",
            'archived_draft' => "Archived {$company} FY25 steering draft",
            'archived_packet' => "Archived completed {$company} review packet",
            'deleted_duplicate' => "Deleted duplicate {$company} escalation note",
            'scenario_plan' => "Build {$company} 90-day scenario plan",
            'finance_guardrails' => "Finalize {$company} investment guardrails",
            'annual_operating_plan' => "Build {$company} annual operating plan assumptions",
            'board_prebrief' => "Prepare {$company} board pre-brief narrative",
            'customer_signal_readout' => "Review {$company} customer signal readout",
            'customer_churn_scenario' => "Model {$company} customer churn scenario",
            'support_capacity_readout' => "Confirm {$company} support capacity readout",
            'people_successor_plan' => "Draft {$company} leadership successor plan",
            'hiring_capacity_review' => "Review {$company} hiring capacity and manager load",
            'executive_offsite_agenda' => "Plan {$company} executive offsite agenda",
            'platform_bet_review' => "Review {$company} platform bet portfolio",
            'ai_governance_signoff' => "Collect {$company} AI governance sign-off",
            'security_exception_review' => "Resolve {$company} security exception review",
            'data_quality_readout' => "Prepare {$company} data quality readout",
            'investor_question_pack' => "Build {$company} investor question pack",
            'ops_cadence_cleanup' => "Clean up {$company} operating cadence notes",
            'executive_inbox_press' => "Capture {$company} press follow-up inbox note",
            'executive_inbox_customer' => "Capture {$company} customer escalation inbox note",
            'executive_inbox_team' => "Capture {$company} team commitment inbox note",
            'completed_customer_sync' => "Completed {$company} customer executive sync",
            'completed_people_review' => "Completed {$company} people readiness review",
            'archived_platform_note' => "Archived {$company} platform steering note",
            'deleted_old_vendor_note' => "Deleted stale {$company} vendor follow-up",
            'partner_scorecard' => "Review {$company} partner scorecard and escalation map",
            'developer_council_agenda' => "Build {$company} developer council agenda",
            'channel_enablement_plan' => "Approve {$company} channel enablement plan",
            'partner_contract_risk' => "Assess {$company} partner contract risk",
            'partner_success_story' => "Draft {$company} partner success story",
            'executive_comms_memo' => "Write {$company} executive communications memo",
            'press_qna_packet' => "Prepare {$company} press Q&A packet",
            'internal_townhall_notes' => "Prepare {$company} internal town hall notes",
            'analyst_response_packet' => "Build {$company} analyst response packet",
            'customer_reference_script' => "Draft {$company} customer reference script",
            'recruiting_calibration' => "Calibrate {$company} leadership recruiting slate",
            'manager_load_review' => "Review {$company} manager load and span of control",
            'org_design_options' => "Prepare {$company} org design options",
            'candidate_close_plan' => "Build {$company} executive candidate close plan",
            'succession_risk_review' => "Review {$company} succession risk register",
            'incident_recovery_plan' => "Coordinate {$company} incident recovery plan",
            'customer_apology_review' => "Review {$company} customer apology language",
            'support_postmortem' => "Write {$company} support postmortem notes",
            'legal_hold_review' => "Confirm {$company} legal hold review",
            'incident_exec_readout' => "Prepare {$company} incident executive readout",
            'metrics_health_review' => "Review {$company} executive metrics health",
            'forecast_dashboard_review' => "Validate {$company} forecast dashboard",
            'launch_metrics_definition' => "Define {$company} launch success metrics",
            'adoption_signal_review' => "Review {$company} adoption signal trends",
            'board_metrics_appendix' => "Build {$company} board metrics appendix",
            'vendor_contract_review' => "Review {$company} vendor contract exposure",
            'supplier_capacity_escalation' => "Escalate {$company} supplier capacity gap",
            'accessibility_vendor_evidence' => "Collect {$company} accessibility vendor evidence",
            'renewal_risk_map' => "Map {$company} renewal and vendor risk",
            'procurement_exception_review' => "Review {$company} procurement exception request",
            'executive_inbox_partner' => "Capture {$company} partner follow-up inbox note",
            'executive_inbox_recruiting' => "Capture {$company} recruiting follow-up inbox note",
            'executive_inbox_incident' => "Capture {$company} incident follow-up inbox note",
            'completed_partner_review' => "Completed {$company} partner ecosystem review",
            'completed_metrics_review' => "Completed {$company} metrics readiness review",
            'archived_incident_postmortem' => "Archived {$company} incident postmortem packet",
            'archived_vendor_renewal' => "Archived {$company} vendor renewal decision",
            'deleted_old_press_note' => "Deleted stale {$company} press draft",
            'deleted_duplicate_recruiting_note' => "Deleted duplicate {$company} recruiting note",
            'deleted_duplicate_metrics_note' => "Deleted duplicate {$company} metrics note",
            'quarterly_operating_followup' => "Schedule {$company} quarterly operating follow-up",
        ];

        return array_merge([
            $this->task('strategy_narrative', $titles, 'strategy', Priority::High, ['board', 'finance'], 2, 'operating_review', 1, 'morning_brief', $this->checklist($company, 'strategy narrative', true)),
            $this->task('operating_review', $titles, 'strategy', Priority::Urgent, ['board', 'blocker'], 7, 'operating_review', 2, 'weekly_review', $this->checklist($company, 'operating review')),
            $this->task('trust_gate', $titles, 'trust', Priority::High, ['legal', 'launch'], 10, 'trust_evidence', 1, 'risk_scan', $this->checklist($company, 'trust gate')),
            $this->task('capacity_plan', $titles, 'launch', Priority::High, ['launch', 'finance', 'blocker'], 14, 'launch_readiness', 2, null, $this->checklist($company, 'capacity plan')),
            $this->task('roadmap_review', $titles, 'launch', Priority::Normal, ['launch', 'people'], 21, 'launch_readiness', 3, null, $this->checklist($company, 'roadmap review')),
            $this->task('field_briefing', $titles, 'field', Priority::High, ['people', 'launch'], 30, 'field_scale', 1, null, $this->checklist($company, 'field briefing')),
            $this->task('investment_memo', $titles, 'strategy', Priority::Normal, ['finance', 'board'], 45, 'operating_review', 3),
            $this->task('blocker_review', $titles, 'strategy', Priority::Urgent, ['blocker', 'board'], null, 'operating_review', 2, null, null, ['due_today' => true]),
            $this->task('overdue_partner', $titles, 'field', Priority::Urgent, ['blocker', 'people'], null, 'field_scale', 2, null, null, ['overdue_days' => 1]),
            $this->task('inbox_board', $titles, null, Priority::Normal, ['board'], null, null, null, null, null, ['inbox_minutes' => 25]),
            $this->task('inbox_analyst', $titles, null, Priority::Low, ['finance'], null, null, null, null, null, ['inbox_minutes' => 70]),
            $this->task('focus_readout', $titles, 'launch', Priority::High, ['launch', 'board'], 4, 'launch_readiness', 1, 'risk_scan', $this->checklist($company, 'product readout', true)),
            $this->task('risk_register', $titles, 'trust', Priority::High, ['legal', 'blocker'], 5, 'trust_evidence', 2, 'risk_scan', $this->checklist($company, 'risk register')),
            $this->task('customer_evidence', $titles, 'field', Priority::Normal, ['people', 'launch'], 18, 'field_scale', 3, null, $this->checklist($company, 'customer evidence', true)),
            $this->task('completed_sync', $titles, 'strategy', Priority::Normal, ['board'], -2, 'operating_review', 1, null, $this->checklist($company, 'leadership sync', true), ['completed' => true]),
            $this->task('completed_budget', $titles, 'strategy', Priority::High, ['finance'], -5, 'operating_review', 1, null, null, ['completed' => true]),
            $this->task('archived_draft', $titles, 'archive', Priority::Low, ['board'], 35, null, null, null, null, ['archived' => true]),
            $this->task('archived_packet', $titles, 'archive', Priority::Normal, ['finance', 'legal'], 28, null, null, null, null, ['completed' => true, 'archived' => true]),
            $this->task('deleted_duplicate', $titles, 'strategy', Priority::Low, ['blocker'], 12, null, null, null, null, ['trashed' => true]),
            $this->task('scenario_plan', $titles, 'strategy', Priority::Low, ['board', 'finance'], 90, 'operating_review', 3),
        ], $this->additionalTasks($company, $titles), $this->extendedManagerTasks($company, $titles));
    }

    /**
     * @param  array<string, string>  $titles
     * @return list<array<string, mixed>>
     */
    private function additionalTasks(string $company, array $titles): array
    {
        return [
            $this->task('finance_guardrails', $titles, 'finance', Priority::High, ['finance', 'governance'], 20, 'finance_governance', 1, 'finance_guardrail_review', $this->checklist($company, 'investment guardrails', true)),
            $this->task('annual_operating_plan', $titles, 'finance', Priority::Urgent, ['finance', 'board', 'governance'], 60, 'finance_governance', 2, null, $this->checklist($company, 'annual operating plan')),
            $this->task('board_prebrief', $titles, 'finance', Priority::High, ['board', 'finance', 'governance'], 3, 'finance_governance', 3, null, $this->checklist($company, 'board pre-brief', true)),
            $this->task('customer_signal_readout', $titles, 'customer', Priority::High, ['customer', 'board'], 24, 'customer_signal', 1, 'customer_signal_review', $this->checklist($company, 'customer signal readout')),
            $this->task('customer_churn_scenario', $titles, 'customer', Priority::Normal, ['customer', 'finance'], 38, 'customer_signal', 2, 'customer_signal_review', $this->checklist($company, 'customer churn scenario')),
            $this->task('support_capacity_readout', $titles, 'customer', Priority::High, ['customer', 'people', 'ops'], 32, 'customer_signal', 3, null, $this->checklist($company, 'support capacity readout')),
            $this->task('people_successor_plan', $titles, 'people', Priority::High, ['people', 'governance'], 42, 'people_cadence', 1, 'people_bench_review', $this->checklist($company, 'leadership successor plan')),
            $this->task('hiring_capacity_review', $titles, 'people', Priority::Normal, ['people', 'finance'], 52, 'people_cadence', 2, 'people_bench_review', $this->checklist($company, 'hiring capacity review')),
            $this->task('executive_offsite_agenda', $titles, 'people', Priority::Normal, ['people', 'board', 'ops'], 50, 'people_cadence', 3, null, $this->checklist($company, 'executive offsite agenda', true)),
            $this->task('platform_bet_review', $titles, 'platform', Priority::Urgent, ['ai', 'security', 'board'], 35, 'platform_bets', 1, 'platform_bet_review', $this->checklist($company, 'platform bet portfolio')),
            $this->task('ai_governance_signoff', $titles, 'platform', Priority::High, ['ai', 'legal', 'governance'], 48, 'platform_bets', 2, 'platform_bet_review', $this->checklist($company, 'AI governance sign-off')),
            $this->task('security_exception_review', $titles, 'platform', Priority::High, ['security', 'legal', 'blocker'], 16, 'platform_bets', 2, null, $this->checklist($company, 'security exception review')),
            $this->task('data_quality_readout', $titles, 'platform', Priority::Normal, ['ai', 'ops'], 29, 'platform_bets', 3, null, $this->checklist($company, 'data quality readout')),
            $this->task('investor_question_pack', $titles, 'finance', Priority::Normal, ['finance', 'board'], 75, 'finance_governance', 3),
            $this->task('ops_cadence_cleanup', $titles, 'strategy', Priority::Low, ['ops'], null, null, null, null, null),
            $this->task('executive_inbox_press', $titles, null, Priority::Normal, ['board'], null, null, null, null, null, ['inbox_minutes' => 18]),
            $this->task('executive_inbox_customer', $titles, null, Priority::Urgent, ['customer', 'blocker'], null, null, null, null, null, ['inbox_minutes' => 35]),
            $this->task('executive_inbox_team', $titles, null, Priority::Normal, ['people', 'ops'], null, null, null, null, null, ['inbox_minutes' => 95]),
            $this->task('completed_customer_sync', $titles, 'customer', Priority::Normal, ['customer'], -3, 'customer_signal', 1, null, $this->checklist($company, 'customer executive sync', true), ['completed' => true]),
            $this->task('completed_people_review', $titles, 'people', Priority::Normal, ['people'], -6, 'people_cadence', 1, null, null, ['completed' => true]),
            $this->task('archived_platform_note', $titles, 'archive', Priority::Low, ['ai', 'governance'], 44, null, null, null, null, ['archived' => true]),
            $this->task('deleted_old_vendor_note', $titles, 'archive', Priority::Low, ['ops'], 22, null, null, null, null, ['trashed' => true]),
        ];
    }

    /**
     * @param  array<string, string>  $titles
     * @return list<array<string, mixed>>
     */
    private function extendedManagerTasks(string $company, array $titles): array
    {
        return [
            $this->task('partner_scorecard', $titles, 'partner', Priority::High, ['partner', 'metrics', 'board'], 26, 'partner_ecosystem', 1, 'partner_ecosystem_review', $this->checklist($company, 'partner scorecard', true)),
            $this->task('developer_council_agenda', $titles, 'partner', Priority::Normal, ['partner', 'communications'], 33, 'partner_ecosystem', 2, null, $this->checklist($company, 'developer council agenda')),
            $this->task('channel_enablement_plan', $titles, 'partner', Priority::High, ['partner', 'people', 'ops'], 39, 'partner_ecosystem', 3, null, $this->checklist($company, 'channel enablement plan')),
            $this->task('partner_contract_risk', $titles, 'partner', Priority::Urgent, ['partner', 'legal', 'blocker'], 9, 'partner_ecosystem', 2, null, $this->checklist($company, 'partner contract risk')),
            $this->task('partner_success_story', $titles, 'partner', Priority::Low, ['partner', 'communications', 'customer'], -8, 'partner_ecosystem', 3, null, null, ['completed' => true]),
            $this->task('executive_comms_memo', $titles, 'communications', Priority::High, ['communications', 'board'], 11, 'executive_communications', 1, 'communications_review', $this->checklist($company, 'executive communications memo', true)),
            $this->task('press_qna_packet', $titles, 'communications', Priority::High, ['communications', 'legal'], 13, 'executive_communications', 2, 'communications_review', $this->checklist($company, 'press Q&A packet')),
            $this->task('internal_townhall_notes', $titles, 'communications', Priority::Normal, ['communications', 'people'], -9, 'executive_communications', 3, null, $this->checklist($company, 'internal town hall notes', true), ['completed' => true]),
            $this->task('analyst_response_packet', $titles, 'communications', Priority::Normal, ['communications', 'finance'], 23, 'executive_communications', 2, null, $this->checklist($company, 'analyst response packet')),
            $this->task('customer_reference_script', $titles, 'communications', Priority::Normal, ['communications', 'customer'], 31, 'executive_communications', 3, null, $this->checklist($company, 'customer reference script')),
            $this->task('recruiting_calibration', $titles, 'recruiting', Priority::High, ['recruiting', 'people'], 46, 'recruiting_org_design', 1, 'recruiting_pipeline_review', $this->checklist($company, 'leadership recruiting slate', true)),
            $this->task('manager_load_review', $titles, 'recruiting', Priority::High, ['recruiting', 'people', 'metrics'], 54, 'recruiting_org_design', 2, 'recruiting_pipeline_review', $this->checklist($company, 'manager load review')),
            $this->task('org_design_options', $titles, 'recruiting', Priority::Normal, ['recruiting', 'governance'], 62, 'recruiting_org_design', 3, null, $this->checklist($company, 'org design options')),
            $this->task('candidate_close_plan', $titles, 'recruiting', Priority::Normal, ['recruiting', 'communications'], 71, 'recruiting_org_design', 2, null, $this->checklist($company, 'executive candidate close plan')),
            $this->task('succession_risk_review', $titles, 'recruiting', Priority::Urgent, ['recruiting', 'blocker', 'governance'], 12, 'recruiting_org_design', 1, null, $this->checklist($company, 'succession risk register')),
            $this->task('incident_recovery_plan', $titles, 'incident', Priority::Urgent, ['incident', 'security', 'customer'], 6, 'incident_recovery', 1, 'incident_recovery_review', $this->checklist($company, 'incident recovery plan', true)),
            $this->task('customer_apology_review', $titles, 'incident', Priority::High, ['incident', 'communications', 'customer'], 8, 'incident_recovery', 2, 'incident_recovery_review', $this->checklist($company, 'customer apology language')),
            $this->task('support_postmortem', $titles, 'incident', Priority::Normal, ['incident', 'ops', 'customer'], 17, 'incident_recovery', 3, null, $this->checklist($company, 'support postmortem notes')),
            $this->task('legal_hold_review', $titles, 'incident', Priority::High, ['incident', 'legal'], 15, 'incident_recovery', 2, null, $this->checklist($company, 'legal hold review')),
            $this->task('incident_exec_readout', $titles, 'incident', Priority::High, ['incident', 'board'], 18, 'incident_recovery', 3, null, $this->checklist($company, 'incident executive readout')),
            $this->task('metrics_health_review', $titles, 'analytics', Priority::High, ['metrics', 'governance'], 27, 'analytics_system', 1, 'metrics_quality_review', $this->checklist($company, 'executive metrics health', true)),
            $this->task('forecast_dashboard_review', $titles, 'analytics', Priority::High, ['metrics', 'finance'], 58, 'analytics_system', 2, 'metrics_quality_review', $this->checklist($company, 'forecast dashboard')),
            $this->task('launch_metrics_definition', $titles, 'analytics', Priority::Normal, ['metrics', 'launch'], 36, 'analytics_system', 3, null, $this->checklist($company, 'launch success metrics')),
            $this->task('adoption_signal_review', $titles, 'analytics', Priority::Normal, ['metrics', 'customer'], 44, 'analytics_system', 2, null, $this->checklist($company, 'adoption signal trends')),
            $this->task('board_metrics_appendix', $titles, 'analytics', Priority::High, ['metrics', 'board'], 79, 'analytics_system', 3, null, $this->checklist($company, 'board metrics appendix')),
            $this->task('vendor_contract_review', $titles, 'vendor', Priority::High, ['vendor', 'finance', 'legal'], 72, 'vendor_governance', 1, null, $this->checklist($company, 'vendor contract exposure', true)),
            $this->task('supplier_capacity_escalation', $titles, 'vendor', Priority::Urgent, ['vendor', 'blocker', 'ops'], null, 'vendor_governance', 2, null, $this->checklist($company, 'supplier capacity gap'), ['overdue_days' => 2]),
            $this->task('accessibility_vendor_evidence', $titles, 'vendor', Priority::Normal, ['vendor', 'accessibility', 'legal'], 88, 'vendor_governance', 3, null, $this->checklist($company, 'accessibility vendor evidence')),
            $this->task('renewal_risk_map', $titles, 'vendor', Priority::Normal, ['vendor', 'finance'], 97, 'vendor_governance', 2, null, $this->checklist($company, 'renewal and vendor risk')),
            $this->task('procurement_exception_review', $titles, 'vendor', Priority::High, ['vendor', 'governance', 'finance'], 24, 'vendor_governance', 1, null, $this->checklist($company, 'procurement exception request')),
            $this->task('executive_inbox_partner', $titles, null, Priority::Normal, ['partner'], null, null, null, null, null, ['inbox_minutes' => 44]),
            $this->task('executive_inbox_recruiting', $titles, null, Priority::Normal, ['recruiting', 'people'], null, null, null, null, null, ['inbox_minutes' => 64]),
            $this->task('executive_inbox_incident', $titles, null, Priority::Urgent, ['incident', 'blocker'], null, null, null, null, null, ['inbox_minutes' => 12]),
            $this->task('completed_partner_review', $titles, 'partner', Priority::Normal, ['partner'], -4, 'partner_ecosystem', 1, null, $this->checklist($company, 'partner ecosystem review', true), ['completed' => true]),
            $this->task('completed_metrics_review', $titles, 'analytics', Priority::Normal, ['metrics'], -7, 'analytics_system', 1, null, null, ['completed' => true]),
            $this->task('archived_incident_postmortem', $titles, 'archive', Priority::Low, ['incident'], 41, null, null, null, null, ['completed' => true, 'archived' => true]),
            $this->task('archived_vendor_renewal', $titles, 'archive', Priority::Low, ['vendor'], 63, null, null, null, null, ['archived' => true]),
            $this->task('deleted_old_press_note', $titles, 'archive', Priority::Low, ['communications'], 20, null, null, null, null, ['trashed' => true]),
            $this->task('deleted_duplicate_recruiting_note', $titles, 'archive', Priority::Low, ['recruiting'], 18, null, null, null, null, ['trashed' => true]),
            $this->task('deleted_duplicate_metrics_note', $titles, 'archive', Priority::Low, ['metrics'], 16, null, null, null, null, ['trashed' => true]),
            $this->task('quarterly_operating_followup', $titles, 'strategy', Priority::Normal, ['board', 'ops', 'metrics'], 112, 'operating_review', 3, 'weekly_review', $this->checklist($company, 'quarterly operating follow-up')),
        ];
    }

    /**
     * @param  array<string, string>  $titles
     * @param  list<string>  $tags
     * @param  list<array{title: string, completed?: bool}>|null  $checklist
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    private function task(
        string $key,
        array $titles,
        ?string $project,
        Priority $priority,
        array $tags,
        ?int $dueDays,
        ?string $goal = null,
        ?int $milestone = null,
        ?string $habit = null,
        ?array $checklist = null,
        array $extra = [],
    ): array {
        return array_filter([
            'key' => $key,
            'title' => $titles[$key],
            'project' => $project,
            'priority' => $priority,
            'due_days' => $dueDays,
            'goal' => $goal,
            'milestone' => $milestone,
            'habit' => $habit,
            'tags' => $tags,
            'checklist' => $checklist,
        ] + $extra, fn (mixed $value): bool => $value !== null);
    }

    /**
     * @return list<array{title: string, completed?: bool}>
     */
    private function checklist(string $company, string $topic, bool $firstCompleted = false): array
    {
        return [
            ['title' => "Collect {$company} source data for {$topic}", 'completed' => $firstCompleted],
            ['title' => "Write executive decision language for {$topic}"],
            ['title' => "Confirm owner, date, and escalation path for {$topic}"],
            ['title' => "Attach finance, legal, people, and customer notes for {$topic}"],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function timeEntries(string $company): array
    {
        return [
            ['todo' => 'strategy_narrative', 'source' => TimeEntrySource::Manual, 'status' => TimeEntryStatus::Completed, 'date_days' => 0, 'minutes' => 55, 'notes' => "{$company} strategy narrative review and executive language pass."],
            ['todo' => 'operating_review', 'source' => TimeEntrySource::Timer, 'status' => TimeEntryStatus::Completed, 'date_days' => 1, 'minutes' => 45, 'notes' => "{$company} operating review timing block with leadership inputs."],
            ['todo' => 'trust_gate', 'pomodoro' => 'trust_gate', 'source' => TimeEntrySource::Pomodoro, 'status' => TimeEntryStatus::Completed, 'date_days' => 2, 'minutes' => 30, 'notes' => "{$company} trust pomodoro completed for launch evidence."],
            ['todo' => 'capacity_plan', 'source' => TimeEntrySource::Manual, 'status' => TimeEntryStatus::Completed, 'date_days' => 3, 'minutes' => 40, 'notes' => "{$company} capacity assumptions reconciled."],
            ['todo' => 'roadmap_review', 'source' => TimeEntrySource::Timer, 'status' => TimeEntryStatus::Completed, 'date_days' => 4, 'minutes' => 35, 'notes' => "{$company} roadmap dependency map reviewed with function leads."],
            ['todo' => 'field_briefing', 'source' => TimeEntrySource::Manual, 'status' => TimeEntryStatus::Completed, 'date_days' => 5, 'minutes' => 60, 'notes' => "{$company} field leadership briefing outline drafted."],
            ['todo' => 'investment_memo', 'source' => TimeEntrySource::Manual, 'status' => TimeEntryStatus::Completed, 'date_days' => 6, 'minutes' => 50, 'notes' => "{$company} investment memo assumptions and budget exposure updated."],
            ['todo' => 'blocker_review', 'source' => TimeEntrySource::Timer, 'status' => TimeEntryStatus::Running, 'date_days' => 0, 'minutes' => 15, 'notes' => "{$company} live blocker review timer still running."],
            ['todo' => 'risk_register', 'source' => TimeEntrySource::Timer, 'status' => TimeEntryStatus::Discarded, 'date_days' => 1, 'minutes' => 8, 'notes' => "{$company} discarded timer after duplicate risk register pass."],
            ['project' => 'strategy', 'source' => TimeEntrySource::Manual, 'status' => TimeEntryStatus::Completed, 'date_days' => 2, 'minutes' => 75, 'notes' => "{$company} executive strategy planning block across multiple tasks."],
            ['project' => 'launch', 'source' => TimeEntrySource::Manual, 'status' => TimeEntryStatus::Completed, 'date_days' => 3, 'minutes' => 65, 'notes' => "{$company} launch planning block for future-dated workstreams."],
            ['todo' => 'focus_readout', 'source' => TimeEntrySource::Manual, 'status' => TimeEntryStatus::Completed, 'date_days' => 0, 'minutes' => 25, 'notes' => "{$company} focus readout preparation notes captured."],
            ['todo' => 'finance_guardrails', 'pomodoro' => 'finance_guardrails', 'source' => TimeEntrySource::Pomodoro, 'status' => TimeEntryStatus::Completed, 'date_days' => 1, 'minutes' => 50, 'notes' => "{$company} investment guardrails reviewed against board expectations."],
            ['todo' => 'annual_operating_plan', 'source' => TimeEntrySource::Manual, 'status' => TimeEntryStatus::Completed, 'date_days' => 2, 'minutes' => 90, 'notes' => "{$company} annual operating plan assumptions updated for future quarters."],
            ['todo' => 'board_prebrief', 'source' => TimeEntrySource::Timer, 'status' => TimeEntryStatus::Completed, 'date_days' => 0, 'minutes' => 35, 'notes' => "{$company} board pre-brief storyline refined with manager-level detail."],
            ['todo' => 'customer_signal_readout', 'pomodoro' => 'customer_signal_readout', 'source' => TimeEntrySource::Timer, 'status' => TimeEntryStatus::Completed, 'date_days' => 3, 'minutes' => 45, 'notes' => "{$company} customer signal readout grouped by account, support, field, and adoption themes."],
            ['todo' => 'customer_churn_scenario', 'source' => TimeEntrySource::Manual, 'status' => TimeEntryStatus::Completed, 'date_days' => 4, 'minutes' => 40, 'notes' => "{$company} customer risk scenario modeled with renewal and satisfaction notes."],
            ['todo' => 'support_capacity_readout', 'source' => TimeEntrySource::Manual, 'status' => TimeEntryStatus::Completed, 'date_days' => 5, 'minutes' => 55, 'notes' => "{$company} support capacity reviewed against launch and field readiness dates."],
            ['todo' => 'people_successor_plan', 'pomodoro' => 'people_successor_plan', 'source' => TimeEntrySource::Pomodoro, 'status' => TimeEntryStatus::Completed, 'date_days' => 2, 'minutes' => 35, 'notes' => "{$company} successor plan reviewed with leadership bench assumptions."],
            ['todo' => 'hiring_capacity_review', 'source' => TimeEntrySource::Manual, 'status' => TimeEntryStatus::Completed, 'date_days' => 6, 'minutes' => 30, 'notes' => "{$company} hiring capacity and manager load reviewed."],
            ['todo' => 'executive_offsite_agenda', 'source' => TimeEntrySource::Manual, 'status' => TimeEntryStatus::Completed, 'date_days' => 1, 'minutes' => 45, 'notes' => "{$company} executive offsite agenda drafted around strategy, people, and customer issues."],
            ['todo' => 'platform_bet_review', 'pomodoro' => 'platform_bet_review', 'source' => TimeEntrySource::Timer, 'status' => TimeEntryStatus::Running, 'date_days' => 0, 'minutes' => 18, 'notes' => "{$company} platform bet review timer still open for a live executive pass."],
            ['todo' => 'ai_governance_signoff', 'source' => TimeEntrySource::Manual, 'status' => TimeEntryStatus::Completed, 'date_days' => 4, 'minutes' => 50, 'notes' => "{$company} AI governance sign-off packet checked for legal and trust evidence."],
            ['todo' => 'security_exception_review', 'source' => TimeEntrySource::Timer, 'status' => TimeEntryStatus::Completed, 'date_days' => 3, 'minutes' => 35, 'notes' => "{$company} security exception reviewed with owner, mitigation, and follow-up date."],
            ['todo' => 'data_quality_readout', 'source' => TimeEntrySource::Manual, 'status' => TimeEntryStatus::Completed, 'date_days' => 7, 'minutes' => 25, 'notes' => "{$company} data quality readout prepared for future platform decisions."],
            ['todo' => 'investor_question_pack', 'source' => TimeEntrySource::Manual, 'status' => TimeEntryStatus::Completed, 'date_days' => 8, 'minutes' => 60, 'notes' => "{$company} investor question pack drafted with finance, customer, and platform context."],
            ['project' => 'customer', 'source' => TimeEntrySource::Manual, 'status' => TimeEntryStatus::Completed, 'date_days' => 2, 'minutes' => 80, 'notes' => "{$company} customer signal synthesis block across support, field, and account notes."],
            ['project' => 'people', 'source' => TimeEntrySource::Manual, 'status' => TimeEntryStatus::Completed, 'date_days' => 3, 'minutes' => 70, 'notes' => "{$company} people leadership planning block for future manager capacity."],
            ['project' => 'finance', 'source' => TimeEntrySource::Manual, 'status' => TimeEntryStatus::Completed, 'date_days' => 4, 'minutes' => 95, 'notes' => "{$company} finance governance block for board assumptions and investment choices."],
            ['project' => 'platform', 'source' => TimeEntrySource::Manual, 'status' => TimeEntryStatus::Completed, 'date_days' => 5, 'minutes' => 85, 'notes' => "{$company} platform strategy block for AI, security, and data quality tradeoffs."],
            ['todo' => 'partner_scorecard', 'pomodoro' => 'partner_scorecard', 'source' => TimeEntrySource::Pomodoro, 'status' => TimeEntryStatus::Completed, 'date_days' => 2, 'minutes' => 40, 'notes' => "{$company} partner scorecard reviewed across ecosystem health, escalations, and executive owners."],
            ['todo' => 'developer_council_agenda', 'source' => TimeEntrySource::Manual, 'status' => TimeEntryStatus::Completed, 'date_days' => 5, 'minutes' => 45, 'notes' => "{$company} developer council agenda shaped around roadmap questions and partner friction."],
            ['todo' => 'channel_enablement_plan', 'source' => TimeEntrySource::Manual, 'status' => TimeEntryStatus::Completed, 'date_days' => 6, 'minutes' => 55, 'notes' => "{$company} channel enablement plan reconciled with field and communications owners."],
            ['todo' => 'partner_contract_risk', 'source' => TimeEntrySource::Timer, 'status' => TimeEntryStatus::Completed, 'date_days' => 1, 'minutes' => 30, 'notes' => "{$company} partner contract risk reviewed with legal, finance, and launch exposure noted."],
            ['todo' => 'executive_comms_memo', 'pomodoro' => 'executive_comms_memo', 'source' => TimeEntrySource::Timer, 'status' => TimeEntryStatus::Completed, 'date_days' => 0, 'minutes' => 32, 'notes' => "{$company} executive communications memo drafted with customer and board language."],
            ['todo' => 'press_qna_packet', 'source' => TimeEntrySource::Manual, 'status' => TimeEntryStatus::Completed, 'date_days' => 2, 'minutes' => 38, 'notes' => "{$company} press Q&A packet checked for approved language and risk boundaries."],
            ['todo' => 'internal_townhall_notes', 'source' => TimeEntrySource::Manual, 'status' => TimeEntryStatus::Completed, 'date_days' => 3, 'minutes' => 42, 'notes' => "{$company} internal town hall notes prepared for leadership clarity and staff follow-up."],
            ['todo' => 'recruiting_calibration', 'source' => TimeEntrySource::Manual, 'status' => TimeEntryStatus::Completed, 'date_days' => 4, 'minutes' => 65, 'notes' => "{$company} leadership recruiting slate calibrated against future org design needs."],
            ['todo' => 'manager_load_review', 'source' => TimeEntrySource::Timer, 'status' => TimeEntryStatus::Completed, 'date_days' => 6, 'minutes' => 35, 'notes' => "{$company} manager load reviewed by team, span, hiring plan, and escalation pressure."],
            ['todo' => 'org_design_options', 'source' => TimeEntrySource::Manual, 'status' => TimeEntryStatus::Completed, 'date_days' => 7, 'minutes' => 50, 'notes' => "{$company} org design options prepared with tradeoffs and leadership coverage notes."],
            ['todo' => 'incident_recovery_plan', 'pomodoro' => 'incident_recovery_plan', 'source' => TimeEntrySource::Timer, 'status' => TimeEntryStatus::Running, 'date_days' => 0, 'minutes' => 20, 'notes' => "{$company} incident recovery review still active for executive follow-up."],
            ['todo' => 'customer_apology_review', 'source' => TimeEntrySource::Manual, 'status' => TimeEntryStatus::Completed, 'date_days' => 1, 'minutes' => 34, 'notes' => "{$company} customer apology language refined with support, legal, and communications input."],
            ['todo' => 'support_postmortem', 'source' => TimeEntrySource::Manual, 'status' => TimeEntryStatus::Completed, 'date_days' => 8, 'minutes' => 48, 'notes' => "{$company} support postmortem notes converted into dated owner commitments."],
            ['todo' => 'metrics_health_review', 'pomodoro' => 'metrics_health_review', 'source' => TimeEntrySource::Pomodoro, 'status' => TimeEntryStatus::Completed, 'date_days' => 2, 'minutes' => 45, 'notes' => "{$company} executive metrics health pass completed with definitions and signal quality checks."],
            ['todo' => 'forecast_dashboard_review', 'source' => TimeEntrySource::Manual, 'status' => TimeEntryStatus::Completed, 'date_days' => 4, 'minutes' => 58, 'notes' => "{$company} forecast dashboard reviewed for board-readiness and future operating assumptions."],
            ['todo' => 'launch_metrics_definition', 'source' => TimeEntrySource::Manual, 'status' => TimeEntryStatus::Completed, 'date_days' => 5, 'minutes' => 36, 'notes' => "{$company} launch metrics definitions aligned with customer adoption and support signals."],
            ['todo' => 'vendor_contract_review', 'pomodoro' => 'vendor_contract_review', 'source' => TimeEntrySource::Pomodoro, 'status' => TimeEntryStatus::Completed, 'date_days' => 3, 'minutes' => 35, 'notes' => "{$company} vendor contract exposure reviewed against finance controls and renewal risk."],
            ['todo' => 'supplier_capacity_escalation', 'source' => TimeEntrySource::Timer, 'status' => TimeEntryStatus::Discarded, 'date_days' => 2, 'minutes' => 12, 'notes' => "{$company} discarded duplicate supplier capacity timer after escalation notes were merged."],
            ['todo' => 'accessibility_vendor_evidence', 'source' => TimeEntrySource::Manual, 'status' => TimeEntryStatus::Completed, 'date_days' => 6, 'minutes' => 28, 'notes' => "{$company} accessibility vendor evidence collected for legal and customer assurance."],
            ['todo' => 'quarterly_operating_followup', 'source' => TimeEntrySource::Manual, 'status' => TimeEntryStatus::Completed, 'date_days' => 9, 'minutes' => 26, 'notes' => "{$company} quarterly operating follow-up scheduled with owner, agenda, and future review date."],
            ['project' => 'partner', 'source' => TimeEntrySource::Manual, 'status' => TimeEntryStatus::Completed, 'date_days' => 2, 'minutes' => 82, 'notes' => "{$company} partner ecosystem planning block across channel, developer, and contract work."],
            ['project' => 'communications', 'source' => TimeEntrySource::Manual, 'status' => TimeEntryStatus::Completed, 'date_days' => 3, 'minutes' => 76, 'notes' => "{$company} executive communications planning block for board, press, and internal narratives."],
            ['project' => 'recruiting', 'source' => TimeEntrySource::Manual, 'status' => TimeEntryStatus::Completed, 'date_days' => 4, 'minutes' => 88, 'notes' => "{$company} recruiting and org design block for leadership coverage and hiring tradeoffs."],
            ['project' => 'incident', 'source' => TimeEntrySource::Manual, 'status' => TimeEntryStatus::Completed, 'date_days' => 5, 'minutes' => 67, 'notes' => "{$company} incident recovery planning block for customer, legal, support, and executive readout."],
            ['project' => 'analytics', 'source' => TimeEntrySource::Manual, 'status' => TimeEntryStatus::Completed, 'date_days' => 6, 'minutes' => 73, 'notes' => "{$company} analytics planning block for dashboards, metric quality, and board appendix work."],
            ['project' => 'vendor', 'source' => TimeEntrySource::Manual, 'status' => TimeEntryStatus::Completed, 'date_days' => 7, 'minutes' => 64, 'notes' => "{$company} vendor governance planning block for contracts, accessibility, capacity, and renewals."],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function automationRuns(string $company): array
    {
        return [
            ['rule' => 'promote', 'status' => AutomationRunStatus::Completed, 'dry_run' => false, 'matched' => 8, 'changed' => 4, 'skipped' => 1, 'message' => "{$company} executive overdue escalation promoted four urgent items."],
            ['rule' => 'promote', 'status' => AutomationRunStatus::Completed, 'dry_run' => true, 'matched' => 9, 'changed' => 0, 'skipped' => 9, 'message' => "{$company} dry-run escalation reviewed future-dated launch work."],
            ['rule' => 'archive', 'status' => AutomationRunStatus::Completed, 'dry_run' => false, 'matched' => 5, 'changed' => 2, 'skipped' => 0, 'message' => "{$company} archive rule moved completed leadership tasks out of active view."],
            ['rule' => 'archive', 'status' => AutomationRunStatus::Failed, 'dry_run' => false, 'matched' => 3, 'changed' => 0, 'skipped' => 3, 'message' => "{$company} archive rule captured a sample failed run for review."],
            ['rule' => 'board', 'status' => AutomationRunStatus::Completed, 'dry_run' => false, 'matched' => 12, 'changed' => 6, 'skipped' => 2, 'message' => "{$company} board-readiness sweep promoted executive review items."],
            ['rule' => 'board', 'status' => AutomationRunStatus::Completed, 'dry_run' => true, 'matched' => 15, 'changed' => 0, 'skipped' => 15, 'message' => "{$company} dry-run board sweep checked future governance work."],
            ['rule' => 'stale', 'status' => AutomationRunStatus::Completed, 'dry_run' => false, 'matched' => 11, 'changed' => 5, 'skipped' => 1, 'message' => "{$company} stale-note archive cleaned old executive context."],
            ['rule' => 'stale', 'status' => AutomationRunStatus::Disabled, 'dry_run' => true, 'matched' => 0, 'changed' => 0, 'skipped' => 0, 'message' => "{$company} stale-note archive skipped because the dry-run was disabled."],
            ['rule' => 'partner', 'status' => AutomationRunStatus::Completed, 'dry_run' => false, 'matched' => 10, 'changed' => 4, 'skipped' => 2, 'message' => "{$company} partner ecosystem sweep promoted contract and developer council follow-ups."],
            ['rule' => 'partner', 'status' => AutomationRunStatus::Completed, 'dry_run' => true, 'matched' => 13, 'changed' => 0, 'skipped' => 13, 'message' => "{$company} dry-run partner sweep checked future ecosystem commitments."],
            ['rule' => 'incident', 'status' => AutomationRunStatus::Completed, 'dry_run' => false, 'matched' => 7, 'changed' => 3, 'skipped' => 1, 'message' => "{$company} incident recovery sweep promoted customer and legal response tasks."],
            ['rule' => 'incident', 'status' => AutomationRunStatus::Failed, 'dry_run' => false, 'matched' => 4, 'changed' => 0, 'skipped' => 4, 'message' => "{$company} incident sweep captured a sample failed recovery escalation."],
            ['rule' => 'recruiting', 'status' => AutomationRunStatus::Completed, 'dry_run' => false, 'matched' => 9, 'changed' => 5, 'skipped' => 0, 'message' => "{$company} recruiting cadence sweep moved leadership hiring blockers into focus."],
            ['rule' => 'recruiting', 'status' => AutomationRunStatus::Completed, 'dry_run' => true, 'matched' => 11, 'changed' => 0, 'skipped' => 11, 'message' => "{$company} dry-run recruiting sweep reviewed future hiring commitments."],
            ['rule' => 'vendor', 'status' => AutomationRunStatus::Completed, 'dry_run' => false, 'matched' => 12, 'changed' => 6, 'skipped' => 1, 'message' => "{$company} vendor governance sweep archived stale renewal context and promoted capacity risk."],
            ['rule' => 'vendor', 'status' => AutomationRunStatus::Disabled, 'dry_run' => true, 'matched' => 0, 'changed' => 0, 'skipped' => 0, 'message' => "{$company} vendor governance dry-run skipped while supplier packet waits for review."],
        ];
    }

    /**
     * @param  array<string, mixed>  $persona
     */
    private function seedPersona(User $user, array $persona): void
    {
        $projects = [];
        foreach ($persona['projects'] as $key => $projectData) {
            $projects[$key] = $this->upsertProject(
                $user,
                $projectData['name'],
                $projectData['color'],
                $projectData['archived'] ?? false,
            );
        }

        $tags = [];
        foreach ($persona['tags'] as $key => $tagData) {
            $tags[$key] = $this->upsertTag($user, $tagData['name'], $tagData['color']);
        }

        $goals = [];
        $milestones = [];
        foreach ($persona['goals'] as $goalIndex => $goalData) {
            $goal = $this->upsertGoal($user, $goalData['title'], [
                'project_id' => $projects[$goalData['project']]->id,
                'description' => $goalData['description'],
                'target_date' => today()->addDays($goalData['target_days'])->toDateString(),
            ]);

            $goals[$goalData['key']] = $goal;
            $milestones[$goalData['key']] = $this->upsertMilestones($goal, $persona['company'], $goalIndex);
        }

        $habits = [];
        foreach ($persona['habits'] as $habitData) {
            $habit = $this->upsertHabit($user, $habitData['title'], [
                'goal_id' => $goals[$habitData['goal']]->id,
                'description' => $habitData['description'],
                'frequency' => $habitData['frequency'],
                'target_count' => $habitData['target_count'],
                'starts_on' => today()->toDateString(),
            ]);

            $habits[$habitData['key']] = $habit;

            foreach (range(0, 9) as $daysAgo) {
                $this->upsertHabitCheckIn($habit, today()->subDays($daysAgo)->toDateString());
            }
        }

        $todos = [];
        foreach ($persona['tasks'] as $taskData) {
            $taskTags = collect($taskData['tags'])
                ->map(fn (string $key): Tag => $tags[$key])
                ->all();

            $todo = $this->upsertTodo($user, $taskData['title'], [
                'project_id' => isset($taskData['project']) ? $projects[$taskData['project']]->id : null,
                'priority' => $taskData['priority'],
                'due_date' => $this->taskDueDate($taskData),
                'is_completed' => $taskData['completed'] ?? false,
                'archived_at' => ($taskData['archived'] ?? false) ? now()->subDays(8) : null,
                'deleted_at' => ($taskData['trashed'] ?? false) ? now()->subDays(3) : null,
                'inbox_captured_at' => isset($taskData['inbox_minutes']) ? now()->subMinutes($taskData['inbox_minutes']) : null,
                'goal_id' => isset($taskData['goal']) ? $goals[$taskData['goal']]->id : null,
                'goal_milestone_id' => isset($taskData['goal'], $taskData['milestone'])
                    ? $milestones[$taskData['goal']][$taskData['milestone']]->id
                    : null,
                'habit_id' => isset($taskData['habit']) ? $habits[$taskData['habit']]->id : null,
            ], ...$taskTags);

            if (isset($taskData['checklist'])) {
                $this->upsertChecklist($todo, $taskData['checklist']);
            }

            $todos[$taskData['key']] = $todo;
        }

        foreach ($persona['dependencies'] as $dependencyData) {
            $this->upsertDependency($todos[$dependencyData['todo']], $todos[$dependencyData['blocker']]);
        }

        foreach ($persona['reminders'] as $reminderData) {
            $this->upsertReminder(
                $todos[$reminderData['todo']],
                $this->reminderDate($reminderData),
                $reminderData['status'],
                $reminderData['skipped_reason'] ?? null,
            );
        }

        $pomodoros = [];
        foreach ($persona['pomodoros'] as $pomodoroData) {
            $pomodoros[$pomodoroData['todo']] = $this->upsertPomodoroSession(
                $todos[$pomodoroData['todo']],
                $pomodoroData['status'],
                $pomodoroData['duration'],
                $pomodoroData['elapsed'],
            );
        }

        foreach ($persona['time_entries'] as $entryData) {
            $this->upsertTimeEntry(
                $user,
                isset($entryData['todo']) ? $todos[$entryData['todo']] : null,
                isset($entryData['project']) ? $projects[$entryData['project']] : null,
                isset($entryData['pomodoro']) ? $pomodoros[$entryData['pomodoro']] : null,
                $entryData,
            );
        }

        $this->seedSavedViews($user, $projects, $tags);
        $this->seedTemplates($user, $persona['company']);
        $this->seedAutomation($user, $persona);
        $this->seedNotifications($user, $persona['company'], $todos);
    }

    /**
     * @param  array<string, Todo>  $todos
     */
    private function seedNotifications(User $user, string $company, array $todos): void
    {
        $this->upsertNotification($user, "{$company} unread reminder", [
            'kind' => 'todo_reminder_due',
            'title' => "{$company} reminder ready for review",
            'message' => "Open the active {$company} strategy reminder and decide the next owner.",
            'action_url' => route('todos.show', $todos['strategy_narrative']),
            'seed_key' => 'unread-reminder',
        ]);

        $this->upsertNotification($user, "{$company} read daily summary", [
            'kind' => 'daily_summary',
            'title' => "{$company} daily summary prepared",
            'message' => "Review today's due and overdue {$company} commitments from the dashboard.",
            'action_url' => route('dashboard'),
            'seed_key' => 'read-summary',
        ], now()->subHours(2));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function upsertNotification(User $user, string $key, array $data, ?DateTimeInterface $readAt = null): void
    {
        $user->notifications()->updateOrCreate(
            ['id' => $this->demoNotificationId($user, $key)],
            [
                'type' => (string) ($data['kind'] ?? 'demo-notification'),
                'data' => $data,
                'read_at' => $readAt,
            ],
        );
    }

    private function demoNotificationId(User $user, string $key): string
    {
        $hash = md5('ruflo-demo-notification:'.$user->email.':'.$key);

        return substr($hash, 0, 8).'-'
            .substr($hash, 8, 4).'-'
            .substr($hash, 12, 4).'-'
            .substr($hash, 16, 4).'-'
            .substr($hash, 20, 12);
    }

    /**
     * @return array<int, GoalMilestone>
     */
    private function upsertMilestones(Goal $goal, string $company, int $goalIndex): array
    {
        $offset = ($goalIndex * 14) + 7;

        return [
            1 => $this->upsertMilestone($goal, "{$company} executive brief accepted", [
                'position' => 1,
                'target_date' => today()->addDays($offset)->toDateString(),
                'completed' => $goalIndex === 0,
            ]),
            2 => $this->upsertMilestone($goal, "{$company} cross-functional risk owners assigned", [
                'position' => 2,
                'target_date' => today()->addDays($offset + 14)->toDateString(),
            ]),
            3 => $this->upsertMilestone($goal, "{$company} future leadership decision package ready", [
                'position' => 3,
                'target_date' => today()->addDays($offset + 28)->toDateString(),
            ]),
        ];
    }

    /**
     * @param  array<string, Project>  $projects
     * @param  array<string, Tag>  $tags
     */
    private function seedSavedViews(User $user, array $projects, array $tags): void
    {
        $this->upsertSavedView($user, 'Executive future launch queue', [
            'project' => (string) $projects['launch']->id,
            'due' => 'upcoming',
            'sort' => 'due',
            'direction' => 'asc',
        ]);

        $this->upsertSavedView($user, 'Board review urgent blockers', [
            'tag' => (string) $tags['blocker']->id,
            'priorityFilter' => Priority::Urgent->value,
            'sort' => 'priority',
            'direction' => 'desc',
        ]);

        $this->upsertSavedView($user, 'Finance and investment commitments', [
            'tag' => (string) $tags['finance']->id,
            'sort' => 'updated',
            'direction' => 'desc',
        ]);

        $this->upsertSavedView($user, 'Legal trust readiness', [
            'project' => (string) $projects['trust']->id,
            'tag' => (string) $tags['legal']->id,
            'sort' => 'due',
            'direction' => 'asc',
        ]);

        $this->upsertSavedView($user, 'Inbox capture for executives', [
            'tab' => 'active',
            'due' => 'without',
            'sort' => 'created',
            'direction' => 'desc',
        ]);

        $this->upsertSavedView($user, 'Customer signal commitments', [
            'project' => (string) $projects['customer']->id,
            'tag' => (string) $tags['customer']->id,
            'sort' => 'due',
            'direction' => 'asc',
        ]);

        $this->upsertSavedView($user, 'People leadership cadence', [
            'project' => (string) $projects['people']->id,
            'tag' => (string) $tags['people']->id,
            'sort' => 'updated',
            'direction' => 'desc',
        ]);

        $this->upsertSavedView($user, 'Finance board governance', [
            'project' => (string) $projects['finance']->id,
            'tag' => (string) $tags['governance']->id,
            'sort' => 'priority',
            'direction' => 'desc',
        ]);

        $this->upsertSavedView($user, 'AI and security platform bets', [
            'project' => (string) $projects['platform']->id,
            'tag' => (string) $tags['ai']->id,
            'sort' => 'due',
            'direction' => 'asc',
        ]);

        $this->upsertSavedView($user, 'Operational cadence cleanup', [
            'tag' => (string) $tags['ops']->id,
            'due' => 'without',
            'sort' => 'updated',
            'direction' => 'asc',
        ]);

        $this->upsertSavedView($user, 'Partner ecosystem escalation map', [
            'project' => (string) $projects['partner']->id,
            'tag' => (string) $tags['partner']->id,
            'sort' => 'priority',
            'direction' => 'desc',
        ]);

        $this->upsertSavedView($user, 'Executive communications queue', [
            'project' => (string) $projects['communications']->id,
            'tag' => (string) $tags['communications']->id,
            'sort' => 'due',
            'direction' => 'asc',
        ]);

        $this->upsertSavedView($user, 'Leadership recruiting runway', [
            'project' => (string) $projects['recruiting']->id,
            'tag' => (string) $tags['recruiting']->id,
            'sort' => 'updated',
            'direction' => 'desc',
        ]);

        $this->upsertSavedView($user, 'Incident recovery command center', [
            'project' => (string) $projects['incident']->id,
            'tag' => (string) $tags['incident']->id,
            'priorityFilter' => Priority::Urgent->value,
            'sort' => 'due',
            'direction' => 'asc',
        ]);

        $this->upsertSavedView($user, 'Metrics and forecast quality', [
            'project' => (string) $projects['analytics']->id,
            'tag' => (string) $tags['metrics']->id,
            'sort' => 'due',
            'direction' => 'asc',
        ]);

        $this->upsertSavedView($user, 'Vendor governance and capacity risk', [
            'project' => (string) $projects['vendor']->id,
            'tag' => (string) $tags['vendor']->id,
            'sort' => 'priority',
            'direction' => 'desc',
        ]);
    }

    private function seedTemplates(User $user, string $company): void
    {
        foreach ($this->templates($company) as $template) {
            $this->upsertTemplate($user, $template['name'], $template);
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function templates(string $company): array
    {
        return [
            [
                'name' => "{$company} executive decision memo",
                'kind' => TaskTemplateKind::Task,
                'visibility' => 'private',
                'title' => "{$company} executive decision memo",
                'description' => "Create a decision-ready memo with context, options, financial exposure, risk owner, and a future follow-up date for {$company} leadership.",
                'priority' => Priority::High,
                'due_offset_days' => 5,
                'project_name' => "{$company} Executive Operations",
                'checklist_items' => ['Write the decision statement', 'Attach finance and risk notes', 'Confirm accountable owner', 'Set the next executive review date'],
            ],
            [
                'name' => "{$company} launch readiness package",
                'kind' => TaskTemplateKind::Project,
                'visibility' => 'shared',
                'title' => "{$company} launch readiness package",
                'description' => "Start a cross-functional future launch package for {$company} with product, field, support, legal, finance, and leadership checkpoints.",
                'priority' => Priority::Urgent,
                'due_offset_days' => 14,
                'project_name' => "{$company} Product Launch Calendar",
                'checklist_items' => ['Define launch date assumptions', 'Map every executive dependency', 'Confirm legal and privacy evidence', 'Prepare field readiness notes', 'Schedule the leadership readout'],
            ],
            [
                'name' => "{$company} weekly manager review",
                'kind' => TaskTemplateKind::Routine,
                'visibility' => 'private',
                'title' => "{$company} weekly manager review",
                'description' => 'Repeatable weekly leadership review for commitments, blockers, habit progress, future milestones, and time tracking.',
                'priority' => Priority::Normal,
                'due_offset_days' => 7,
                'project_name' => "{$company} Executive Operations",
                'checklist_items' => ['Review overdue and blocked work', 'Update goal milestones', 'Confirm next-week focus blocks'],
            ],
            [
                'name' => "{$company} trust evidence checklist",
                'kind' => TaskTemplateKind::Checklist,
                'visibility' => 'private',
                'title' => "{$company} trust evidence checklist",
                'description' => 'Checklist for privacy, compliance, security, accessibility, and customer evidence before executive sign-off.',
                'priority' => Priority::High,
                'due_offset_days' => 21,
                'project_name' => "{$company} Privacy and Platform Trust",
                'checklist_items' => ['Collect legal notes', 'Collect security review', 'Collect accessibility status', 'Collect customer support readiness'],
            ],
            [
                'name' => "{$company} blocked escalation",
                'kind' => TaskTemplateKind::Task,
                'visibility' => 'private',
                'title' => "{$company} blocked escalation",
                'description' => 'Use when a future commitment needs a named blocker, executive escalation owner, and review reminder.',
                'priority' => Priority::Urgent,
                'due_offset_days' => 1,
                'project_name' => "{$company} Executive Operations",
                'checklist_items' => ['Name the blocker', 'Name the unblock owner', 'Choose escalation channel', 'Set reminder and review date'],
            ],
            [
                'name' => "{$company} customer signal review",
                'kind' => TaskTemplateKind::Routine,
                'visibility' => 'private',
                'title' => "{$company} customer signal review",
                'description' => 'Recurring customer-signal pass for support escalations, account health, adoption risk, and executive follow-up.',
                'priority' => Priority::High,
                'due_offset_days' => 2,
                'project_name' => "{$company} Customer Experience War Room",
                'checklist_items' => ['Group customer notes by theme', 'Identify executive-visible risk', 'Name an owner for every escalation', 'Set a dated customer follow-up'],
            ],
            [
                'name' => "{$company} finance guardrail pack",
                'kind' => TaskTemplateKind::Checklist,
                'visibility' => 'shared',
                'title' => "{$company} finance guardrail pack",
                'description' => 'Board-grade finance pack for investment limits, forecast exposure, operating plan assumptions, and approval notes.',
                'priority' => Priority::High,
                'due_offset_days' => 10,
                'project_name' => "{$company} Finance and Board Governance",
                'checklist_items' => ['Confirm spend owner', 'Confirm forecast sensitivity', 'Attach approval path', 'Set board pre-brief date'],
            ],
            [
                'name' => "{$company} leadership bench review",
                'kind' => TaskTemplateKind::Routine,
                'visibility' => 'private',
                'title' => "{$company} leadership bench review",
                'description' => 'Manager review for succession, hiring load, team capacity, offsite agenda, and next leadership commitments.',
                'priority' => Priority::Normal,
                'due_offset_days' => 14,
                'project_name' => "{$company} Leadership Talent Bench",
                'checklist_items' => ['Review successor readiness', 'Review hiring load', 'Name manager escalations', 'Book leadership follow-up'],
            ],
            [
                'name' => "{$company} AI platform decision review",
                'kind' => TaskTemplateKind::Project,
                'visibility' => 'shared',
                'title' => "{$company} AI platform decision review",
                'description' => 'Future-bet review for AI/platform work covering governance, security exceptions, data quality, customer impact, and executive sign-off.',
                'priority' => Priority::Urgent,
                'due_offset_days' => 21,
                'project_name' => "{$company} AI Platform Future Bets",
                'checklist_items' => ['Write platform bet thesis', 'Attach governance evidence', 'Attach security review', 'Attach data quality notes', 'Set executive decision date'],
            ],
            [
                'name' => "{$company} operating cadence cleanup",
                'kind' => TaskTemplateKind::Task,
                'visibility' => 'private',
                'title' => "{$company} operating cadence cleanup",
                'description' => 'Clean stale notes, close redundant reminders, archive old packets, and preserve only useful management context.',
                'priority' => Priority::Low,
                'due_offset_days' => 30,
                'project_name' => "{$company} Executive Operations",
                'checklist_items' => ['Archive stale packets', 'Close duplicate notes', 'Keep future commitments visible', 'Confirm cleanup report'],
            ],
            [
                'name' => "{$company} partner ecosystem escalation map",
                'kind' => TaskTemplateKind::Project,
                'visibility' => 'shared',
                'title' => "{$company} partner ecosystem escalation map",
                'description' => 'Partner and developer ecosystem package with contract risk, council agenda, channel enablement, and executive escalation owners.',
                'priority' => Priority::High,
                'due_offset_days' => 18,
                'project_name' => "{$company} Partner and Developer Ecosystem",
                'checklist_items' => ['Review partner health', 'Name escalation owners', 'Confirm contract risk', 'Schedule developer council follow-up'],
            ],
            [
                'name' => "{$company} executive communications packet",
                'kind' => TaskTemplateKind::Checklist,
                'visibility' => 'private',
                'title' => "{$company} executive communications packet",
                'description' => 'Reusable communications packet for board language, press Q&A, internal town hall notes, analyst response, and customer references.',
                'priority' => Priority::High,
                'due_offset_days' => 9,
                'project_name' => "{$company} Executive Communications",
                'checklist_items' => ['Draft approved language', 'Check legal risk', 'Align customer examples', 'Set publication owner'],
            ],
            [
                'name' => "{$company} leadership recruiting runway",
                'kind' => TaskTemplateKind::Routine,
                'visibility' => 'private',
                'title' => "{$company} leadership recruiting runway",
                'description' => 'Leadership hiring and org-design cadence for recruiting calibration, manager span, candidate close plans, and succession risks.',
                'priority' => Priority::Normal,
                'due_offset_days' => 21,
                'project_name' => "{$company} Hiring and Org Design",
                'checklist_items' => ['Review open leadership roles', 'Calibrate candidate slate', 'Review manager load', 'Update succession risk'],
            ],
            [
                'name' => "{$company} incident recovery playbook",
                'kind' => TaskTemplateKind::Project,
                'visibility' => 'shared',
                'title' => "{$company} incident recovery playbook",
                'description' => 'Incident recovery playbook covering customer apology, support postmortem, legal hold, security response, and executive readout.',
                'priority' => Priority::Urgent,
                'due_offset_days' => 3,
                'project_name' => "{$company} Incident and Customer Recovery",
                'checklist_items' => ['Name incident owner', 'Approve customer language', 'Complete support postmortem', 'Book executive readout'],
            ],
            [
                'name' => "{$company} metrics and forecast review",
                'kind' => TaskTemplateKind::Routine,
                'visibility' => 'private',
                'title' => "{$company} metrics and forecast review",
                'description' => 'Metrics review for dashboard quality, forecast assumptions, launch metrics, adoption signals, and board appendix readiness.',
                'priority' => Priority::High,
                'due_offset_days' => 12,
                'project_name' => "{$company} Metrics and Insights",
                'checklist_items' => ['Check metric definitions', 'Validate forecast dashboard', 'Review adoption signals', 'Refresh board appendix'],
            ],
            [
                'name' => "{$company} vendor governance review",
                'kind' => TaskTemplateKind::Checklist,
                'visibility' => 'private',
                'title' => "{$company} vendor governance review",
                'description' => 'Vendor governance review for contract exposure, capacity risk, accessibility evidence, renewal mapping, and procurement exceptions.',
                'priority' => Priority::High,
                'due_offset_days' => 28,
                'project_name' => "{$company} Vendor and Capacity Governance",
                'checklist_items' => ['Review contract exposure', 'Escalate capacity risk', 'Collect accessibility evidence', 'Map renewal and procurement exceptions'],
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $persona
     */
    private function seedAutomation(User $user, array $persona): void
    {
        $promoteRule = $this->upsertAutomationRule(
            $user,
            "{$persona['company']} executive overdue escalation",
            AutomationRuleKind::PromoteOverdueTasks,
            ['minimum_priority' => Priority::High->value, 'notify' => 'dashboard'],
        );

        $archiveRule = $this->upsertAutomationRule(
            $user,
            "{$persona['company']} completed leadership archive",
            AutomationRuleKind::ArchiveCompletedTasks,
            ['days' => 14, 'role' => $persona['role']],
        );

        $boardRule = $this->upsertAutomationRule(
            $user,
            "{$persona['company']} board readiness sweep",
            AutomationRuleKind::PromoteOverdueTasks,
            ['minimum_priority' => Priority::Normal->value, 'tag' => 'board', 'notify' => 'saved_view'],
        );

        $staleRule = $this->upsertAutomationRule(
            $user,
            "{$persona['company']} stale executive context archive",
            AutomationRuleKind::ArchiveCompletedTasks,
            ['days' => 30, 'include_archived_projects' => true],
        );

        $partnerRule = $this->upsertAutomationRule(
            $user,
            "{$persona['company']} partner ecosystem escalation",
            AutomationRuleKind::PromoteOverdueTasks,
            ['minimum_priority' => Priority::High->value, 'tag' => 'partner', 'notify' => 'partner_saved_view'],
        );

        $incidentRule = $this->upsertAutomationRule(
            $user,
            "{$persona['company']} incident recovery escalation",
            AutomationRuleKind::PromoteOverdueTasks,
            ['minimum_priority' => Priority::Urgent->value, 'tag' => 'incident', 'notify' => 'incident_saved_view'],
        );

        $recruitingRule = $this->upsertAutomationRule(
            $user,
            "{$persona['company']} recruiting cadence escalation",
            AutomationRuleKind::PromoteOverdueTasks,
            ['minimum_priority' => Priority::High->value, 'tag' => 'recruiting', 'notify' => 'leadership_saved_view'],
        );

        $vendorRule = $this->upsertAutomationRule(
            $user,
            "{$persona['company']} vendor governance archive",
            AutomationRuleKind::ArchiveCompletedTasks,
            ['days' => 45, 'tag' => 'vendor', 'include_archived_projects' => true],
        );

        $rules = [
            'promote' => $promoteRule,
            'archive' => $archiveRule,
            'board' => $boardRule,
            'stale' => $staleRule,
            'partner' => $partnerRule,
            'incident' => $incidentRule,
            'recruiting' => $recruitingRule,
            'vendor' => $vendorRule,
        ];

        foreach ($persona['automation_runs'] as $runData) {
            $this->upsertAutomationRun($rules[$runData['rule']], $runData);
        }
    }

    private function upsertProject(User $user, string $name, string $color, bool $archived = false): Project
    {
        $project = Project::query()
            ->where('user_id', $user->id)
            ->where('name', $name)
            ->first() ?? new Project;

        $project->forceFill([
            'user_id' => $user->id,
            'name' => $name,
            'color' => $color,
            'archived_at' => $archived ? ($project->archived_at ?? now()->subMonth()) : null,
        ])->save();

        return $project;
    }

    private function upsertTag(User $user, string $name, string $color): Tag
    {
        $tag = Tag::query()
            ->where('user_id', $user->id)
            ->where('name', $name)
            ->first() ?? new Tag;

        $tag->forceFill([
            'user_id' => $user->id,
            'name' => $name,
            'color' => $color,
        ])->save();

        return $tag;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function upsertTodo(User $user, string $title, array $attributes, Tag ...$tags): Todo
    {
        $todo = Todo::query()
            ->withTrashed()
            ->where('user_id', $user->id)
            ->where('title', $title)
            ->first() ?? new Todo;

        $todo->forceFill([
            'user_id' => $user->id,
            'title' => $title,
            'project_id' => $attributes['project_id'] ?? null,
            'priority' => $attributes['priority'],
            'due_date' => $attributes['due_date'] ?? null,
            'is_completed' => $attributes['is_completed'] ?? false,
            'archived_at' => $attributes['archived_at'] ?? null,
            'deleted_at' => $attributes['deleted_at'] ?? null,
            'inbox_captured_at' => $attributes['inbox_captured_at'] ?? null,
            'goal_id' => $attributes['goal_id'] ?? null,
            'goal_milestone_id' => $attributes['goal_milestone_id'] ?? null,
            'habit_id' => $attributes['habit_id'] ?? null,
        ])->save();

        $todo->tags()->sync(collect($tags)->pluck('id')->all());

        return $todo;
    }

    /**
     * @param  array<string, mixed>  $taskData
     */
    private function taskDueDate(array $taskData): ?string
    {
        if (($taskData['due_today'] ?? false) === true) {
            return today()->toDateString();
        }

        if (isset($taskData['overdue_days'])) {
            return today()->subDays($taskData['overdue_days'])->toDateString();
        }

        if (array_key_exists('due_days', $taskData) && $taskData['due_days'] !== null) {
            return today()->addDays($taskData['due_days'])->toDateString();
        }

        return null;
    }

    /**
     * @param  list<array{title: string, completed?: bool}>  $items
     */
    private function upsertChecklist(Todo $todo, array $items): void
    {
        foreach ($items as $index => $itemData) {
            $completed = $itemData['completed'] ?? false;

            $item = TodoChecklistItem::query()
                ->where('todo_id', $todo->id)
                ->where('title', $itemData['title'])
                ->first() ?? new TodoChecklistItem;

            $item->forceFill([
                'user_id' => $todo->user_id,
                'todo_id' => $todo->id,
                'title' => $itemData['title'],
                'is_completed' => $completed,
                'position' => $index + 1,
                'completed_at' => $completed ? ($item->completed_at ?? now()->subDay()) : null,
            ])->save();
        }
    }

    /**
     * @param  array{project_id: int, description: string, target_date: string}  $attributes
     */
    private function upsertGoal(User $user, string $title, array $attributes): Goal
    {
        $goal = Goal::query()
            ->where('user_id', $user->id)
            ->where('title', $title)
            ->first() ?? new Goal;

        $goal->forceFill([
            'user_id' => $user->id,
            'project_id' => $attributes['project_id'],
            'title' => $title,
            'description' => $attributes['description'],
            'target_date' => $attributes['target_date'],
            'completed_at' => null,
            'archived_at' => null,
        ])->save();

        return $goal;
    }

    /**
     * @param  array{position: int, target_date: string, completed?: bool}  $attributes
     */
    private function upsertMilestone(Goal $goal, string $title, array $attributes): GoalMilestone
    {
        $milestone = GoalMilestone::query()
            ->where('goal_id', $goal->id)
            ->where('title', $title)
            ->first() ?? new GoalMilestone;

        $milestone->forceFill([
            'user_id' => $goal->user_id,
            'goal_id' => $goal->id,
            'title' => $title,
            'target_date' => $attributes['target_date'],
            'position' => $attributes['position'],
            'completed_at' => ($attributes['completed'] ?? false) ? ($milestone->completed_at ?? now()->subDay()) : null,
        ])->save();

        return $milestone;
    }

    /**
     * @param  array{goal_id: int, description: string, frequency: HabitFrequency, target_count: int, starts_on: string}  $attributes
     */
    private function upsertHabit(User $user, string $title, array $attributes): Habit
    {
        $habit = Habit::query()
            ->where('user_id', $user->id)
            ->where('title', $title)
            ->first() ?? new Habit;

        $habit->forceFill([
            'user_id' => $user->id,
            'goal_id' => $attributes['goal_id'],
            'title' => $title,
            'description' => $attributes['description'],
            'frequency' => $attributes['frequency'],
            'target_count' => $attributes['target_count'],
            'starts_on' => $attributes['starts_on'],
            'archived_at' => null,
        ])->save();

        return $habit;
    }

    private function upsertHabitCheckIn(Habit $habit, string $occurredOn): HabitCheckIn
    {
        $checkIn = HabitCheckIn::query()
            ->where('habit_id', $habit->id)
            ->whereDate('occurred_on', $occurredOn)
            ->first() ?? new HabitCheckIn;

        $checkIn->forceFill([
            'user_id' => $habit->user_id,
            'habit_id' => $habit->id,
            'occurred_on' => $occurredOn,
            'checked_at' => $checkIn->checked_at ?? now()->setTime(9, 0),
        ])->save();

        return $checkIn;
    }

    private function upsertDependency(Todo $todo, Todo $dependsOn): TodoDependency
    {
        $dependency = TodoDependency::query()
            ->where('user_id', $todo->user_id)
            ->where('todo_id', $todo->id)
            ->where('depends_on_todo_id', $dependsOn->id)
            ->first() ?? new TodoDependency;

        $dependency->forceFill([
            'user_id' => $todo->user_id,
            'todo_id' => $todo->id,
            'depends_on_todo_id' => $dependsOn->id,
        ])->save();

        return $dependency;
    }

    /**
     * @param  array{days?: int, minutes?: int}  $reminderData
     */
    private function reminderDate(array $reminderData): DateTimeInterface|string
    {
        if (isset($reminderData['minutes'])) {
            return now()->addMinutes($reminderData['minutes']);
        }

        return now()->addDays($reminderData['days'] ?? 1);
    }

    private function upsertReminder(
        Todo $todo,
        DateTimeInterface|string $remindAt,
        ReminderStatus $status,
        ?string $skippedReason = null,
    ): Reminder {
        $reminder = Reminder::query()
            ->where('user_id', $todo->user_id)
            ->where('todo_id', $todo->id)
            ->first() ?? new Reminder;

        $reminder->forceFill([
            'user_id' => $todo->user_id,
            'todo_id' => $todo->id,
            'remind_at' => $remindAt,
            'status' => $status,
            'processed_at' => $status === ReminderStatus::Processed ? ($reminder->processed_at ?? now()->subMinutes(15)) : null,
            'skipped_at' => $status === ReminderStatus::Skipped ? ($reminder->skipped_at ?? now()->subMinutes(20)) : null,
            'skipped_reason' => $status === ReminderStatus::Skipped ? $skippedReason : null,
            'last_error' => null,
        ])->save();

        return $reminder;
    }

    private function upsertPomodoroSession(
        Todo $todo,
        PomodoroSessionStatus $status,
        int $durationMinutes,
        int $elapsedSeconds,
    ): PomodoroSession {
        $session = PomodoroSession::query()
            ->where('user_id', $todo->user_id)
            ->where('todo_id', $todo->id)
            ->where('status', $status->value)
            ->first() ?? new PomodoroSession;

        $startedAt = now()->subSeconds(max($elapsedSeconds, 60));

        $session->forceFill([
            'user_id' => $todo->user_id,
            'todo_id' => $todo->id,
            'duration_minutes' => $durationMinutes,
            'elapsed_seconds' => $elapsedSeconds,
            'status' => $status,
            'started_at' => $startedAt,
            'last_started_at' => $status === PomodoroSessionStatus::Running ? now()->subSeconds(60) : null,
            'paused_at' => $status === PomodoroSessionStatus::Paused ? now()->subMinutes(2) : null,
            'completed_at' => $status === PomodoroSessionStatus::Completed ? now()->subMinutes(10) : null,
            'abandoned_at' => $status === PomodoroSessionStatus::Abandoned ? now()->subMinutes(5) : null,
        ])->save();

        return $session;
    }

    /**
     * @param  array{source: TimeEntrySource, status: TimeEntryStatus, date_days: int, minutes: int, notes: string}  $entryData
     */
    private function upsertTimeEntry(
        User $user,
        ?Todo $todo,
        ?Project $project,
        ?PomodoroSession $pomodoroSession,
        array $entryData,
    ): TimeEntry {
        $entryDate = today()->subDays($entryData['date_days'])->toDateString();
        $query = TimeEntry::query()
            ->where('user_id', $user->id)
            ->where('source', $entryData['source']->value)
            ->whereDate('entry_date', $entryDate)
            ->where('notes', $entryData['notes']);

        $this->whereNullable($query, 'todo_id', $todo?->id);
        $this->whereNullable($query, 'project_id', $project?->id ?? $todo?->project_id);
        $this->whereNullable($query, 'pomodoro_session_id', $pomodoroSession?->id);

        $entry = $query->first() ?? new TimeEntry;
        $startedAt = $entryData['status'] === TimeEntryStatus::Running
            ? now()->subMinutes($entryData['minutes'])
            : today()->subDays($entryData['date_days'])->setTime(10, 0);

        $entry->forceFill([
            'user_id' => $user->id,
            'todo_id' => $todo?->id,
            'project_id' => $project?->id ?? $todo?->project_id,
            'pomodoro_session_id' => $pomodoroSession?->id,
            'duration_seconds' => $entryData['status'] === TimeEntryStatus::Running ? 0 : $entryData['minutes'] * 60,
            'source' => $entryData['source'],
            'status' => $entryData['status'],
            'entry_date' => $entryDate,
            'started_at' => $startedAt,
            'stopped_at' => $entryData['status'] === TimeEntryStatus::Running ? null : $startedAt->copy()->addMinutes($entryData['minutes']),
            'notes' => $entryData['notes'],
        ])->save();

        return $entry;
    }

    /**
     * @param  Builder<TimeEntry>  $query
     */
    private function whereNullable(Builder $query, string $column, ?int $value): void
    {
        if ($value === null) {
            $query->whereNull($column);

            return;
        }

        $query->where($column, $value);
    }

    /**
     * @param  array<string, mixed>  $criteria
     */
    private function upsertSavedView(User $user, string $name, array $criteria): SavedTodoView
    {
        $savedView = SavedTodoView::query()
            ->where('user_id', $user->id)
            ->where('name', $name)
            ->first() ?? new SavedTodoView;

        $savedView->forceFill([
            'user_id' => $user->id,
            'name' => $name,
            'criteria' => SavedTodoViewData::normalizeCriteria($criteria),
        ])->save();

        return $savedView;
    }

    /**
     * @param  array{name: string, kind: TaskTemplateKind, visibility: string, title: string, description: string, priority: Priority, due_offset_days: int, project_name: string, checklist_items: list<string>}  $attributes
     */
    private function upsertTemplate(User $user, string $name, array $attributes): TodoTemplate
    {
        $template = TodoTemplate::query()
            ->where('user_id', $user->id)
            ->where('name', $name)
            ->first() ?? new TodoTemplate;

        $template->forceFill([
            'user_id' => $user->id,
            'name' => $name,
            'kind' => $attributes['kind'],
            'visibility' => $attributes['visibility'],
            'title' => $attributes['title'],
            'description' => $attributes['description'],
            'priority' => $attributes['priority'],
            'due_offset_days' => $attributes['due_offset_days'],
            'project_name' => $attributes['project_name'],
            'checklist_items' => $attributes['checklist_items'],
        ])->save();

        return $template;
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    private function upsertAutomationRule(
        User $user,
        string $name,
        AutomationRuleKind $kind,
        array $settings,
    ): AutomationRule {
        $automationRule = AutomationRule::query()
            ->where('user_id', $user->id)
            ->where('name', $name)
            ->first() ?? new AutomationRule;

        $automationRule->forceFill([
            'user_id' => $user->id,
            'name' => $name,
            'kind' => $kind,
            'is_enabled' => true,
            'settings' => array_replace($kind->defaultSettings(), $settings),
            'last_run_at' => now()->subHours(2),
            'last_status' => AutomationRunStatus::Completed,
            'last_message' => "{$name} completed during executive demo seeding.",
        ])->save();

        return $automationRule;
    }

    /**
     * @param  array{status: AutomationRunStatus, dry_run: bool, matched: int, changed: int, skipped: int, message: string}  $runData
     */
    private function upsertAutomationRun(AutomationRule $rule, array $runData): AutomationRuleRun
    {
        $run = AutomationRuleRun::query()
            ->where('automation_rule_id', $rule->id)
            ->where('message', $runData['message'])
            ->first() ?? new AutomationRuleRun;

        $startedAt = today()->setTime(15, 0)->subDays($runData['dry_run'] ? 1 : 0);

        $run->forceFill([
            'user_id' => $rule->user_id,
            'automation_rule_id' => $rule->id,
            'status' => $runData['status'],
            'dry_run' => $runData['dry_run'],
            'matched_count' => $runData['matched'],
            'changed_count' => $runData['changed'],
            'skipped_count' => $runData['skipped'],
            'details' => [
                'source' => 'executive_demo_seed',
                'rule' => $rule->name,
                'matched_count' => $runData['matched'],
                'changed_count' => $runData['changed'],
                'skipped_count' => $runData['skipped'],
            ],
            'message' => $runData['message'],
            'started_at' => $startedAt,
            'finished_at' => $startedAt->copy()->addMinutes(3),
        ])->save();

        return $run;
    }

    private function canSeedDemoUsers(): bool
    {
        if (! (bool) config('demo.login_panel.enabled', true)) {
            return false;
        }

        return in_array((string) config('app.env'), config('demo.login_panel.environments', []), true);
    }
}
