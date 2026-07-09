<?php
if (!defined('ABSPATH')) {
    exit;
}

function myavana_render_gamification_dashboard_section($context = [])
{
    $gamification_metrics = $context['gamification_metrics'] ?? [];
    $challenge_rows = $context['challenge_rows'] ?? [];
    $reward_user_rows = $context['reward_user_rows'] ?? [];
    $reward_field_labels = $context['reward_field_labels'] ?? [];
    $reward_settings = $context['reward_settings'] ?? [];
    $challenge_definitions = $context['challenge_definitions'] ?? [];
    $challenge_metric_options = $context['challenge_metric_options'] ?? [];
    $role_options = $context['role_options'] ?? [];
    $challenge_category_options = [
        'weekly' => 'Weekly',
        'routine' => 'Routine',
        'ai' => 'AI',
        'streak' => 'Streak',
        'community' => 'Community',
        'media' => 'Media',
        'custom' => 'Custom',
    ];
    $audience_options = ['all' => 'All Roles'];
    foreach ((array) $role_options as $role_key => $role_meta) {
        $audience_options[$role_key] = translate_user_role($role_meta['name'] ?? $role_key);
    }
    $avg_points_per_user = !empty($gamification_metrics['rewarded_users'])
        ? round(((int) ($gamification_metrics['points_awarded'] ?? 0)) / max(1, (int) $gamification_metrics['rewarded_users']), 1)
        : 0;
    $challenge_completion_rate = !empty($gamification_metrics['rewarded_users'])
        ? round((((int) ($gamification_metrics['challenge_participants'] ?? 0)) / max(1, (int) $gamification_metrics['rewarded_users'])) * 100, 1)
        : 0;

    $chart_challenge_labels = array_map(static function ($row) {
        return $row['title'];
    }, $challenge_rows);
    $chart_challenge_values = array_map(static function ($row) {
        return (int) $row['completions'];
    }, $challenge_rows);

    $reward_reason_rows = [];
    foreach ((array) ($context['reward_reason_rows'] ?? []) as $row) {
        $reward_reason_rows[] = $row;
    }
    $chart_reward_reason_labels = array_map(static function ($row) {
        return $row['reason'];
    }, $reward_reason_rows);
    $chart_reward_reason_values = array_map(static function ($row) {
        return (int) $row['points'];
    }, $reward_reason_rows);
    ?>
    <h2 class="myavana-section-title">Gamification Control Center</h2>
    <div class="myavana-grid">
        <div class="myavana-card"><div class="metric-label">Reward Events</div><div class="metric-value"><?php echo esc_html(number_format_i18n((int) ($gamification_metrics['reward_events'] ?? 0))); ?></div><div class="metric-subtle">Ledger events in selected range</div></div>
        <div class="myavana-card"><div class="metric-label">Points Awarded</div><div class="metric-value"><?php echo esc_html(number_format_i18n((int) ($gamification_metrics['points_awarded'] ?? 0))); ?></div><div class="metric-subtle">Total points distributed</div></div>
        <div class="myavana-card"><div class="metric-label">Rewarded Users</div><div class="metric-value"><?php echo esc_html(number_format_i18n((int) ($gamification_metrics['rewarded_users'] ?? 0))); ?></div><div class="metric-subtle">Unique users receiving rewards</div></div>
        <div class="myavana-card"><div class="metric-label">Challenge Completions</div><div class="metric-value"><?php echo esc_html(number_format_i18n((int) ($gamification_metrics['challenge_completions'] ?? 0))); ?></div><div class="metric-subtle"><?php echo esc_html(number_format_i18n((int) ($gamification_metrics['challenge_participants'] ?? 0))); ?> participants</div></div>
        <div class="myavana-card"><div class="metric-label">Avg Points Per User</div><div class="metric-value"><?php echo esc_html(number_format_i18n((float) $avg_points_per_user, 1)); ?></div><div class="metric-subtle">Reward intensity</div></div>
        <div class="myavana-card"><div class="metric-label">Challenge Reach</div><div class="metric-value"><?php echo esc_html(number_format_i18n((float) $challenge_completion_rate, 1)); ?>%</div><div class="metric-subtle">Rewarded users who completed challenges</div></div>
    </div>

    <div class="myavana-grid">
        <div class="chart-wrap">
            <h3>Challenge Completions</h3>
            <canvas id="myavanaChallengeCompletionChart"></canvas>
        </div>
        <div class="chart-wrap">
            <h3>Points by Reward Reason</h3>
            <canvas id="myavanaRewardReasonChart"></canvas>
        </div>
    </div>

    <div class="myavana-grid">
        <div class="myavana-card">
            <h3>Challenge Performance</h3>
            <table>
                <thead><tr><th>Challenge</th><th>Completions</th><th>Participants</th><th>Points Awarded</th></tr></thead>
                <tbody>
                    <?php if (!empty($challenge_rows)): ?>
                        <?php foreach ($challenge_rows as $row): ?>
                            <tr>
                                <td><?php echo esc_html($row['title']); ?></td>
                                <td><?php echo esc_html(number_format_i18n((int) $row['completions'])); ?></td>
                                <td><?php echo esc_html(number_format_i18n((int) $row['participants'])); ?></td>
                                <td><?php echo esc_html(number_format_i18n((int) $row['points'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="4">No challenge completions in selected range.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="myavana-card">
            <h3>Top Rewarded Users</h3>
            <table>
                <thead><tr><th>User</th><th>Email</th><th>Reward Events</th><th>Points</th></tr></thead>
                <tbody>
                    <?php if (!empty($reward_user_rows)): ?>
                        <?php foreach ($reward_user_rows as $row): ?>
                            <tr>
                                <td><?php echo esc_html($row['display_name']); ?></td>
                                <td><?php echo esc_html($row['user_email']); ?></td>
                                <td><?php echo esc_html(number_format_i18n((int) $row['events'])); ?></td>
                                <td><?php echo esc_html(number_format_i18n((int) $row['points'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="4">No reward activity in selected range.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="myavana-card">
        <h3>Reward Value Tuning</h3>
        <form method="post">
            <?php wp_nonce_field('myavana_save_gamification_settings', 'myavana_gamification_nonce'); ?>
            <input type="hidden" name="myavana_gamification_action" value="save_rewards" />
            <table>
                <thead><tr><th>Reward</th><th>Points</th></tr></thead>
                <tbody>
                    <?php foreach ($reward_field_labels as $reward_key => $reward_label): ?>
                        <tr>
                            <td><?php echo esc_html($reward_label); ?></td>
                            <td><input type="number" min="0" name="myavana_reward_settings[<?php echo esc_attr($reward_key); ?>]" value="<?php echo esc_attr(intval($reward_settings[$reward_key] ?? 0)); ?>" /></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <p><button type="submit" class="button button-primary">Save Reward Settings</button></p>
        </form>
    </div>

    <div class="myavana-card">
        <h3>Active Challenge Definitions</h3>
        <form method="post" id="myavanaChallengeForm">
            <?php wp_nonce_field('myavana_save_gamification_settings', 'myavana_gamification_nonce'); ?>
            <input type="hidden" name="myavana_gamification_action" value="save_challenges" />
            <table>
                <thead><tr><th>ID</th><th>Title</th><th>Category</th><th>Metric</th><th>Target</th><th>Reward</th><th>Status</th><th>Audience</th><th>Start</th><th>End</th><th>Description</th></tr></thead>
                <tbody id="myavanaChallengeTableBody">
                    <?php foreach ($challenge_definitions as $index => $challenge): ?>
                        <tr>
                            <td><input type="text" name="myavana_challenges[<?php echo esc_attr($index); ?>][id]" value="<?php echo esc_attr($challenge['id']); ?>" /></td>
                            <td><input type="text" name="myavana_challenges[<?php echo esc_attr($index); ?>][title]" value="<?php echo esc_attr($challenge['title']); ?>" /></td>
                            <td>
                                <select name="myavana_challenges[<?php echo esc_attr($index); ?>][category]">
                                    <?php foreach ($challenge_category_options as $category_key => $category_label): ?>
                                        <option value="<?php echo esc_attr($category_key); ?>" <?php selected($challenge['category'] ?? 'custom', $category_key); ?>><?php echo esc_html($category_label); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td>
                                <select name="myavana_challenges[<?php echo esc_attr($index); ?>][metric]">
                                    <?php foreach ($challenge_metric_options as $metric_key => $metric_label): ?>
                                        <option value="<?php echo esc_attr($metric_key); ?>" <?php selected($challenge['metric'], $metric_key); ?>><?php echo esc_html($metric_label); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td><input type="number" min="1" name="myavana_challenges[<?php echo esc_attr($index); ?>][target]" value="<?php echo esc_attr(intval($challenge['target'])); ?>" /></td>
                            <td><input type="number" min="0" name="myavana_challenges[<?php echo esc_attr($index); ?>][reward_points]" value="<?php echo esc_attr(intval($challenge['reward_points'])); ?>" /></td>
                            <td>
                                <select name="myavana_challenges[<?php echo esc_attr($index); ?>][status]">
                                    <option value="active" <?php selected($challenge['status'], 'active'); ?>>Active</option>
                                    <option value="paused" <?php selected($challenge['status'], 'paused'); ?>>Paused</option>
                                </select>
                            </td>
                            <td>
                                <select name="myavana_challenges[<?php echo esc_attr($index); ?>][audience]">
                                    <?php foreach ($audience_options as $audience_key => $audience_label): ?>
                                        <option value="<?php echo esc_attr($audience_key); ?>" <?php selected($challenge['audience'] ?? 'all', $audience_key); ?>><?php echo esc_html($audience_label); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td><input type="date" name="myavana_challenges[<?php echo esc_attr($index); ?>][start_date]" value="<?php echo esc_attr($challenge['start_date'] ?? ''); ?>" /></td>
                            <td><input type="date" name="myavana_challenges[<?php echo esc_attr($index); ?>][end_date]" value="<?php echo esc_attr($challenge['end_date'] ?? ''); ?>" /></td>
                            <td><textarea name="myavana_challenges[<?php echo esc_attr($index); ?>][description]" rows="2"><?php echo esc_textarea($challenge['description']); ?></textarea></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <p>
                <button type="button" class="button" id="myavanaAddChallengeRow">Add Challenge Row</button>
                <button type="submit" class="button button-primary">Save Challenges</button>
            </p>
        </form>
    </div>

    <script>
        (function() {
            if (typeof Chart !== 'undefined') {
                const challengeLabels = <?php echo wp_json_encode($chart_challenge_labels); ?>;
                const challengeValues = <?php echo wp_json_encode($chart_challenge_values); ?>;
                const rewardReasonLabels = <?php echo wp_json_encode($chart_reward_reason_labels); ?>;
                const rewardReasonValues = <?php echo wp_json_encode($chart_reward_reason_values); ?>;

                const challengeCanvas = document.getElementById('myavanaChallengeCompletionChart');
                if (challengeCanvas) {
                    new Chart(challengeCanvas, {
                        type: 'bar',
                        data: { labels: challengeLabels, datasets: [{ label: 'Completions', data: challengeValues, backgroundColor: '#1f3b61' }] },
                        options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true } } }
                    });
                }

                const rewardReasonCanvas = document.getElementById('myavanaRewardReasonChart');
                if (rewardReasonCanvas) {
                    new Chart(rewardReasonCanvas, {
                        type: 'bar',
                        data: { labels: rewardReasonLabels, datasets: [{ label: 'Points', data: rewardReasonValues, backgroundColor: '#c9895c' }] },
                        options: { responsive: true, maintainAspectRatio: false, indexAxis: 'y', scales: { x: { beginAtZero: true } } }
                    });
                }
            }

            const addRowButton = document.getElementById('myavanaAddChallengeRow');
            const tbody = document.getElementById('myavanaChallengeTableBody');
            if (!addRowButton || !tbody) {
                return;
            }

            addRowButton.addEventListener('click', function() {
                const index = tbody.querySelectorAll('tr').length;
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td><input type="text" name="myavana_challenges[${index}][id]" value="" /></td>
                    <td><input type="text" name="myavana_challenges[${index}][title]" value="" /></td>
                    <td>
                        <select name="myavana_challenges[${index}][category]">
                            <?php foreach ($challenge_category_options as $category_key => $category_label): ?>
                                <option value="<?php echo esc_attr($category_key); ?>"><?php echo esc_html($category_label); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                    <td>
                        <select name="myavana_challenges[${index}][metric]">
                            <?php foreach ($challenge_metric_options as $metric_key => $metric_label): ?>
                                <option value="<?php echo esc_attr($metric_key); ?>"><?php echo esc_html($metric_label); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                    <td><input type="number" min="1" name="myavana_challenges[${index}][target]" value="1" /></td>
                    <td><input type="number" min="0" name="myavana_challenges[${index}][reward_points]" value="0" /></td>
                    <td>
                        <select name="myavana_challenges[${index}][status]">
                            <option value="active">Active</option>
                            <option value="paused">Paused</option>
                        </select>
                    </td>
                    <td>
                        <select name="myavana_challenges[${index}][audience]">
                            <?php foreach ($audience_options as $audience_key => $audience_label): ?>
                                <option value="<?php echo esc_attr($audience_key); ?>"><?php echo esc_html($audience_label); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                    <td><input type="date" name="myavana_challenges[${index}][start_date]" value="" /></td>
                    <td><input type="date" name="myavana_challenges[${index}][end_date]" value="" /></td>
                    <td><textarea name="myavana_challenges[${index}][description]" rows="2"></textarea></td>
                `;
                tbody.appendChild(row);
            });
        })();
    </script>
    <?php
}
