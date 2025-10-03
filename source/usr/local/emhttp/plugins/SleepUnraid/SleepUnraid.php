<?php
// Definições de caminhos e nome do plugin para fácil manutenção
define('PLUGIN_NAME', 'SleepUnraid');
define('PLUGIN_PATH', '/usr/local/emhttp/plugins/' . PLUGIN_NAME);
define('CONFIG_DIR', '/boot/config/plugins/' . PLUGIN_NAME);
define('CONFIG_FILE', CONFIG_DIR . '/' . PLUGIN_NAME . '.cfg');

// Função para escrever o array de configuração no arquivo .cfg
function write_config($config) {
    $file_content = "";
    foreach ($config as $key => $value) {
        // Garante que valores com espaços sejam colocados entre aspas
        $file_content .= $key . '="' . $value . '"' . PHP_EOL;
    }
    // file_put_contents garante que a escrita seja atômica
    file_put_contents(CONFIG_FILE, $file_content);
}

// Valores padrão para todas as configurações
$defaults = [
    'ENABLED' => 'no',
    'EXCLUDE_DAYS' => '',
    'EXCLUDE_HOURS' => '',
    'WAIT_ARRAY_INACTIVE' => 'no',
    // MONITOR_DISKS será tratado dinamicamente mais tarde
    'WAIT_NET_INACTIVE' => 'no',
    'MONITOR_NET_INTERFACES' => '',
    'NET_THRESHOLD' => '0',
    'WAIT_USER_INACTIVE' => 'no',
    'USER_INACTIVITY_TIMEOUT' => '15',
    'SLEEP_TIMER' => '30',
    'WOL_OPTION' => 'm',
    'STOP_VMS' => 'no',
    'START_VMS_ON_WAKE' => '',
    'STOP_DOCKERS' => 'no',
    'START_DOCKERS_ON_WAKE' => '',
    'PRE_SLEEP_COMMANDS' => '',
    'POST_WAKE_COMMANDS' => '',
    'RESTART_NET_ON_WAKE' => 'no',
    'RENEW_DHCP_ON_WAKE' => 'no',
    'DEBUG_MODE' => 'no'
];

// Carrega a configuração existente, se houver
if (file_exists(CONFIG_FILE)) {
    $config = parse_ini_file(CONFIG_FILE);
} else {
    $config = [];
}
// Mescla a configuração carregada com os padrões para garantir que todas as chaves existam
$config = array_merge($defaults, $config);

// --- Lógica para lidar com o envio do formulário ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Se o botão "Padrão" foi clicado
    if (isset($_POST['restore_defaults'])) {
        $config = $defaults;
    } else { // Se o botão "Aplicar" foi clicado
        // 1. Ativar Sleep Unraid
        $config['ENABLED'] = isset($_POST['enabled']) ? 'yes' : 'no';

        // 2. Dias e Horas a excluir (transforma o array de checkboxes em string)
        $config['EXCLUDE_DAYS'] = isset($_POST['exclude_days']) ? implode(',', $_POST['exclude_days']) : '';
        $config['EXCLUDE_HOURS'] = isset($_POST['exclude_hours']) ? implode(',', $_POST['exclude_hours']) : '';

        // 3. Inatividade do Array
        $config['WAIT_ARRAY_INACTIVE'] = $_POST['wait_array_inactive'] ?? $defaults['WAIT_ARRAY_INACTIVE'];

        // 4. Monitorar Discos (Placeholder - a lógica será mais complexa)

        // 5. Inatividade da Rede
        $config['WAIT_NET_INACTIVE'] = isset($_POST['wait_net_inactive']) ? 'yes' : 'no';
        $config['MONITOR_NET_INTERFACES'] = isset($_POST['monitor_net_interfaces']) ? implode(',', $_POST['monitor_net_interfaces']) : '';
        $config['NET_THRESHOLD'] = $_POST['net_threshold'] ?? $defaults['NET_THRESHOLD'];
        
        // 8. Inatividade do Usuário
        $config['WAIT_USER_INACTIVE'] = isset($_POST['wait_user_inactive']) ? 'yes' : 'no';
        $config['USER_INACTIVITY_TIMEOUT'] = $_POST['user_inactivity_timeout'] ?? $defaults['USER_INACTIVITY_TIMEOUT'];
        
        // 9. Tempo para Sono
        $config['SLEEP_TIMER'] = $_POST['sleep_timer'] ?? $defaults['SLEEP_TIMER'];
        
        // 10. Opção WOL
        $config['WOL_OPTION'] = $_POST['wol_option'] ?? $defaults['WOL_OPTION'];
        
        // 11, 13. Parar VMs e Dockers
        $config['STOP_VMS'] = isset($_POST['stop_vms']) ? 'yes' : 'no';
        $config['STOP_DOCKERS'] = isset($_POST['stop_dockers']) ? 'yes' : 'no';
        
        // 12, 14. Iniciar VMs e Dockers (Placeholder)
        
        // 15, 16. Comandos Personalizados
        $config['PRE_SLEEP_COMMANDS'] = $_POST['pre_sleep_commands'] ?? '';
        $config['POST_WAKE_COMMANDS'] = $_POST['post_wake_commands'] ?? '';

        // 17, 18. Opções de Rede ao Acordar
        $config['RESTART_NET_ON_WAKE'] = isset($_POST['restart_net_on_wake']) ? 'yes' : 'no';
        $config['RENEW_DHCP_ON_WAKE'] = isset($_POST['renew_dhcp_on_wake']) ? 'yes' : 'no';

        // 19. Modo Debug
        $config['DEBUG_MODE'] = $_POST['debug_mode'] ?? $defaults['DEBUG_MODE'];
    }

    // Salva a configuração no arquivo e reinicia o serviço
    write_config($config);
    shell_exec('/etc/rc.d/rc.SleepUnraid restart');
    
    // Redireciona para a mesma página para evitar reenvio do formulário
    header("Location: /Settings/".PLUGIN_NAME);
    exit();
}

// --- Funções auxiliares para o HTML ---
function is_checked($key, $config) {
    return ($config[$key] ?? 'no') === 'yes' ? 'checked' : '';
}

function is_day_checked($day, $config) {
    $days = explode(',', $config['EXCLUDE_DAYS'] ?? '');
    return in_array($day, $days) ? 'checked' : '';
}

function is_hour_checked($hour, $config) {
    $hours = explode(',', $config['EXCLUDE_HOURS'] ?? '');
    return in_array((string)$hour, $hours) ? 'checked' : '';
}

// Inclui o cabeçalho padrão das páginas do Unraid
include_once('/usr/local/emhttp/plugins/dynamix/include/Helpers.php');
$var = parse_ini_file('/var/local/emhttp/var.ini');
$unraid_version = $var['version'];
$helpers = new Helpers();
$helpers->page_head(PLUGIN_NAME . " Settings");
?>

<body>
<div id="container">

    <!-- Conteúdo da página de configurações -->
    <form id="sleepunraid_form" method="post" action="/Settings/<?= PLUGIN_NAME ?>">

        <?php $helpers->page_header(PLUGIN_NAME . " Settings", "Control when and how your server goes to sleep."); ?>

        <div class="content">
            <!-- Tabela para organizar as configurações -->
            <table class="settings">
                <!-- 1. Ativar Plugin -->
                <tr>
                    <td>1. Ativar Sleep Unraid</td>
                    <td>
                        <input type="checkbox" name="enabled" <?= is_checked('ENABLED', $config) ?>>
                        <span class="label">Ativar o monitoramento de inatividade.</span>
                    </td>
                </tr>

                <!-- 2. Excluir Dias -->
                <tr>
                    <td>2. Excluir Dias</td>
                    <td>
                        <?php
                        $days = ['Mon' => 'Segunda', 'Tue' => 'Terça', 'Wed' => 'Quarta', 'Thu' => 'Quinta', 'Fri' => 'Sexta', 'Sat' => 'Sábado', 'Sun' => 'Domingo'];
                        foreach ($days as $key => $name) {
                            echo "<label style='margin-right: 10px;'><input type='checkbox' name='exclude_days[]' value='$key' ".is_day_checked($key, $config)."> $name</label>";
                        }
                        ?>
                    </td>
                </tr>

                <!-- 2. Excluir Horas -->
                <tr>
                    <td>3. Excluir Horas</td>
                    <td style="max-width: 800px;">
                        <?php
                        for ($i = 0; $i < 24; $i++) {
                            $hour_label = sprintf('%02d:00 - %02d:59', $i, $i);
                            echo "<label style='display: inline-block; width: 150px; margin: 2px;'><input type='checkbox' name='exclude_hours[]' value='$i' ".is_hour_checked($i, $config)."> $hour_label</label>";
                        }
                        ?>
                    </td>
                </tr>

                <!-- 3. Inatividade do Array -->
                <tr>
                    <td>4. Esperar pela inatividade do Array</td>
                    <td>
                        <select name="wait_array_inactive">
                            <option value="no" <?= $config['WAIT_ARRAY_INACTIVE'] == 'no' ? 'selected' : '' ?>>Não</option>
                            <option value="yes_exclude_cache" <?= $config['WAIT_ARRAY_INACTIVE'] == 'yes_exclude_cache' ? 'selected' : '' ?>>Sim (excluindo Cache)</option>
                            <option value="yes_include_cache" <?= $config['WAIT_ARRAY_INACTIVE'] == 'yes_include_cache' ? 'selected' : '' ?>>Sim (incluindo Cache)</option>
                        </select>
                    </td>
                </tr>
                
                <!-- 4. Monitorar Discos -->
                <tr>
                    <td>5. Monitorar Discos</td>
                    <td>
                        <div class="warning">
                            <strong>A ser implementado:</strong> A lista de discos aparecerá aqui.
                        </div>
                    </td>
                </tr>

                <!-- 5, 6, 7. Inatividade da Rede -->
                <tr>
                    <td>6. Esperar por inatividade da Rede</td>
                    <td>
                        <input type="checkbox" name="wait_net_inactive" <?= is_checked('WAIT_NET_INACTIVE', $config) ?>>
                        <span class="label">Monitorar tráfego de rede.</span>
                    </td>
                </tr>
                <tr>
                    <td>7. Interfaces de Rede Monitoradas</td>
                    <td>
                        <div class="info">
                            <strong>A ser implementado:</strong> A lista de interfaces de rede (eth0, br0, etc.) aparecerá aqui.
                        </div>
                    </td>
                </tr>
                <tr>
                    <td>8. Filtro de Limite de Atividade</td>
                    <td>
                        <select name="net_threshold">
                            <option value="0" <?= $config['NET_THRESHOLD'] == '0' ? 'selected' : '' ?>>Todo Tráfego [0 kb/s]</option>
                            <option value="20" <?= $config['NET_THRESHOLD'] == '20' ? 'selected' : '' ?>>Ultra Sensível [20 kb/s]</option>
                            <option value="100" <?= $config['NET_THRESHOLD'] == '100' ? 'selected' : '' ?>>Baixo Tráfego [100 kb/s]</option>
                            <option value="500" <?= $config['NET_THRESHOLD'] == '500' ? 'selected' : '' ?>>Médio Tráfego [500 kb/s]</option>
                            <option value="1000" <?= $config['NET_THRESHOLD'] == '1000' ? 'selected' : '' ?>>Alto Tráfego [1 Mb/s]</option>
                        </select>
                    </td>
                </tr>

                <!-- 8. Inatividade do Usuário -->
                <tr>
                    <td>9. Esperar por Inatividade do Usuário (Web, SSH, Telnet)</td>
                    <td>
                        <input type="checkbox" name="wait_user_inactive" <?= is_checked('WAIT_USER_INACTIVE', $config) ?>>
                        <input type="number" name="user_inactivity_timeout" value="<?= htmlspecialchars($config['USER_INACTIVITY_TIMEOUT']) ?>" min="1" style="width: 60px;"> minutos
                    </td>
                </tr>
                
                <!-- 9. Tempo para Sono -->
                <tr>
                    <td>10. Tempo para Sono após última atividade</td>
                    <td>
                        <input type="number" name="sleep_timer" value="<?= htmlspecialchars($config['SLEEP_TIMER']) ?>" min="5" style="width: 60px;"> minutos
                    </td>
                </tr>
                
                <!-- 10. Opção WOL -->
                <tr>
                    <td>11. Definir opção WOL antes de dormir</td>
                    <td>
                        <select name="wol_option">
                            <option value="p" <?= $config['WOL_OPTION'] == 'p' ? 'selected' : '' ?>>p (Phy activity)</option>
                            <option value="u" <?= $config['WOL_OPTION'] == 'u' ? 'selected' : '' ?>>u (Unicast)</option>
                            <option value="m" <?= $config['WOL_OPTION'] == 'm' ? 'selected' : '' ?>>m (Multicast)</option>
                            <option value="b" <?= $config['WOL_OPTION'] == 'b' ? 'selected' : '' ?>>b (Broadcast)</option>
                            <option value="g" <?= $config['WOL_OPTION'] == 'g' ? 'selected' : '' ?>>g (MagicPacket™)</option>
                        </select>
                    </td>
                </tr>

                <!-- 11, 12, 13, 14. VMs e Dockers -->
                <tr>
                    <td>12. Parar todas as VMs antes do Sono</td>
                    <td><input type="checkbox" name="stop_vms" <?= is_checked('STOP_VMS', $config) ?>></td>
                </tr>
                <tr>
                    <td>13. Iniciar VM ao acordar</td>
                    <td><div class="info"><strong>A ser implementado:</strong> Lista de VMs.</div></td>
                </tr>
                <tr>
                    <td>14. Parar todos os Containers Docker antes do Sono</td>
                    <td><input type="checkbox" name="stop_dockers" <?= is_checked('STOP_DOCKERS', $config) ?>></td>
                </tr>
                <tr>
                    <td>15. Iniciar Container Docker ao acordar</td>
                    <td><div class="info"><strong>A ser implementado:</strong> Lista de Containers.</div></td>
                </tr>
                
                <!-- 15, 16. Comandos Personalizados -->
                <tr>
                    <td>16. Executar comandos personalizados antes de dormir (1 por linha)</td>
                    <td><textarea name="pre_sleep_commands" rows="4" style="width: 100%;"><?= htmlspecialchars($config['PRE_SLEEP_COMMANDS']) ?></textarea></td>
                </tr>
                <tr>
                    <td>17. Executar comandos personalizados após acordar (1 por linha)</td>
                    <td><textarea name="post_wake_commands" rows="4" style="width: 100%;"><?= htmlspecialchars($config['POST_WAKE_COMMANDS']) ?></textarea></td>
                </tr>
                
                <!-- 17, 18. Opções de Rede Pós-Sono -->
                <tr>
                    <td>18. Iniciar interface de rede ao acordar</td>
                    <td><input type="checkbox" name="restart_net_on_wake" <?= is_checked('RESTART_NET_ON_WAKE', $config) ?>><span class="label"> (Marque se tiver problemas de rede ao acordar)</span></td>
                </tr>
                <tr>
                    <td>19. Renovar DHCP após acordar</td>
                    <td><input type="checkbox" name="renew_dhcp_on_wake" <?= is_checked('RENEW_DHCP_ON_WAKE', $config) ?>></td>
                </tr>
                
                <!-- 19. Debug -->
                <tr>
                    <td>20. Ativar DEBUG</td>
                    <td>
                         <select name="debug_mode">
                            <option value="no" <?= $config['DEBUG_MODE'] == 'no' ? 'selected' : '' ?>>Não</option>
                            <option value="syslog" <?= $config['DEBUG_MODE'] == 'syslog' ? 'selected' : '' ?>>Syslog</option>
                            <option value="syslog_flash" <?= $config['DEBUG_MODE'] == 'syslog_flash' ? 'selected' : '' ?>>Syslog e Flash</option>
                        </select>
                    </td>
                </tr>
            </table>
        </div>
        
        <!-- Botões -->
        <div id="buttons">
            <input type="submit" name="apply_changes" value="Aplicar">
            <input type="submit" name="restore_defaults" value="Padrão" formnovalidate>
            <button type="button" onclick="window.location.href='/Settings'">Feito</button>
        </div>

    </form>
</div>

<?php
// Inclui o rodapé padrão das páginas do Unraid
$helpers->page_footer();
?>
</body>
</html>
