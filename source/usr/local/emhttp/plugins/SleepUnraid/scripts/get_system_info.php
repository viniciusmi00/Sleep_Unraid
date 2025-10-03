<?php
// Define que a saída deste script é do tipo JSON
header('Content-Type: application/json');

/**
 * Coleta informações sobre os discos do sistema.
 * Utiliza o arquivo 'disks.ini' do Unraid para obter detalhes precisos.
 * @return array Lista de discos com seus detalhes.
 */
function getDisksInfo() {
    $disks = [];
    if (file_exists('/var/local/emhttp/disks.ini')) {
        $ini_array = parse_ini_file('/var/local/emhttp/disks.ini', true);
        foreach ($ini_array as $key => $details) {
            // Filtra apenas as entradas que representam um disco físico
            if (isset($details['device'])) {
                $disks[] = [
                    'id' => $key, // Ex: disk1, cache
                    'device' => $details['device'], // Ex: sdb, nvme0n1
                    'type' => $details['type'], // Ex: Parity, Data, Cache
                    'name' => $details['name'] // Nome amigável definido pelo usuário
                ];
            }
        }
    }
    return $disks;
}

/**
 * Coleta os nomes de todas as interfaces de rede disponíveis.
 * @return array Lista de nomes de interfaces.
 */
function getNetworkInterfaces() {
    // Lista os diretórios em /sys/class/net, que correspondem às interfaces
    $output = shell_exec('ls /sys/class/net');
    // Converte a string de saída em um array, removendo linhas vazias
    return array_filter(explode(PHP_EOL, $output));
}

/**
 * Coleta os nomes de todas as Máquinas Virtuais (VMs) definidas.
 * Utiliza o comando 'virsh' do libvirt.
 * @return array Lista de nomes de VMs.
 */
function getVMs() {
    // '-q' para modo silencioso, '--all' para todas (ligadas e desligadas), '--name' para obter apenas os nomes
    $output = shell_exec('virsh -q list --all --name');
    return array_filter(explode(PHP_EOL, $output));
}

/**
 * Coleta os nomes de todos os containers Docker.
 * @return array Lista de nomes de containers.
 */
function getDockers() {
    // '--format "{{.Names}}"' para obter apenas a coluna de nomes
    $output = shell_exec('docker ps -a --format "{{.Names}}"');
    return array_filter(explode(PHP_EOL, $output));
}

// Monta o array final com todas as informações coletadas
$system_info = [
    'disks' => getDisksInfo(),
    'network_interfaces' => getNetworkInterfaces(),
    'vms' => getVMs(),
    'dockers' => getDockers()
];

// Codifica o array em formato JSON e o envia como resposta
echo json_encode($system_info, JSON_PRETTY_PRINT);
?>
