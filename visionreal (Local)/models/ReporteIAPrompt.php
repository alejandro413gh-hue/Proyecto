<?php
class ReporteIAPrompt {
    public function build(array $data): string {
        $meta = $data['meta'] ?? [];
        $empresa = $data['empresa'] ?? [];
        $ventas = $data['ventas'] ?? [];
        $inventario = $data['inventario'] ?? [];
        $pedidos = $data['pedidos'] ?? [];

        $lines = [];
        $lines[] = 'Eres un analista comercial senior para retail y respondes solo en Markdown.';
        $lines[] = 'Redacta como un consultor de negocio: tono ejecutivo, claro, natural y profesional.';
        $lines[] = 'Tu objetivo es convertir los datos en lectura estratégica, no repetirlos de forma mecánica.';
        $lines[] = 'Estructura exacta: # Resumen Ejecutivo, ## Ventas, ## Inventario, ## Pedidos, ## Riesgos, ## Recomendaciones, ## Acciones de 7 días.';
        $lines[] = 'Reglas de estilo: usa frases completas, evita listas ásperas o repetitivas, no inventes datos y no copies los números sin interpretación.';
        $lines[] = 'En el resumen ejecutivo escribe 2 o 3 frases breves con la lectura principal del negocio.';
        $lines[] = 'En cada sección usa hasta 2 bullets, pero que cada bullet aporte contexto o decisión, no solo un dato bruto.';
        $lines[] = 'Cuando una métrica esté en cero, exprésala con lenguaje natural como "actividad nula" o "sin movimiento reciente".';
        $lines[] = 'Cierra con recomendaciones concretas, priorizadas y viables para los próximos 7 días.';
        $lines[] = '';
        $lines[] = 'Fecha: ' . ($meta['fecha'] ?? date('Y-m-d H:i:s'));
        $lines[] = 'Zona horaria: ' . ($meta['zona_horaria'] ?? date_default_timezone_get());
        $lines[] = '';
        $lines[] = 'Resumen numérico:';
        $lines[] = '- Productos totales: ' . $this->int($empresa['productos_totales'] ?? 0);
        $lines[] = '- Clientes totales: ' . $this->int($empresa['clientes_totales'] ?? 0);
        $lines[] = '- Ventas totales: ' . $this->int($empresa['ventas_totales'] ?? 0);
        $lines[] = '- Ventas hoy: ' . $this->int($empresa['ventas_hoy'] ?? 0);
        $lines[] = '- Ventas del mes: ' . $this->int($empresa['ventas_mes'] ?? 0);
        $lines[] = '- Ingresos totales: ' . $this->money($empresa['ingresos_totales'] ?? 0);
        $lines[] = '- Ingresos hoy: ' . $this->money($empresa['ingresos_hoy'] ?? 0);
        $lines[] = '- Ingresos del mes: ' . $this->money($empresa['ingresos_mes'] ?? 0);
        $lines[] = '- Ticket promedio: ' . $this->money($empresa['ticket_promedio'] ?? 0);
        $lines[] = '- Stock bajo: ' . $this->int($empresa['productos_stock_bajo'] ?? 0);
        $lines[] = '- Agotados: ' . $this->int($empresa['productos_agotados'] ?? 0);
        $lines[] = '- Pedidos pendientes: ' . $this->int($pedidos['pendientes_total'] ?? 0);
        $lines[] = '- Pedidos entregados: ' . $this->int($pedidos['entregados_total'] ?? 0);
        $lines[] = '';
        $lines[] = 'Enfoque editorial: prioriza claridad, síntesis y criterio comercial. Evita el tono robótico y redacta con una lectura humana del negocio.';
        $lines[] = '';

        if (!empty($ventas['por_dia'])) {
            $lines[] = 'Ventas últimos 7 días:';
            foreach (array_slice($ventas['por_dia'], 0, 7) as $item) {
                $lines[] = '- ' . ($item['dia'] ?? 'N/D') . ': ' . $this->money($item['total'] ?? 0) . ' | ' . $this->int($item['cantidad'] ?? 0) . ' ventas';
            }
            $lines[] = '';
        }

        if (!empty($ventas['top_productos'])) {
            $lines[] = 'Top productos:';
            foreach (array_slice($ventas['top_productos'], 0, 5) as $item) {
                $lines[] = '- ' . $this->cell($item['nombre'] ?? 'N/D') . ': ' . $this->int($item['total_vendido'] ?? 0) . ' uds | ' . $this->money($item['total_ingresos'] ?? 0);
            }
            $lines[] = '';
        }

        if (!empty($inventario['stock_bajo'])) {
            $lines[] = 'Stock bajo:';
            foreach (array_slice($inventario['stock_bajo'], 0, 5) as $item) {
                $lines[] = '- ' . $this->cell($item['nombre'] ?? 'N/D') . ' | stock ' . $this->int($item['stock'] ?? 0) . ' | ' . $this->cell($item['categoria_nombre'] ?? 'Sin categoría');
            }
            $lines[] = '';
        }

        if (!empty($inventario['agotados'])) {
            $lines[] = 'Agotados:';
            foreach (array_slice($inventario['agotados'], 0, 5) as $item) {
                $lines[] = '- ' . $this->cell($item['nombre'] ?? 'N/D') . ' | ' . $this->cell($item['categoria_nombre'] ?? 'Sin categoría');
            }
            $lines[] = '';
        }

        if (!empty($pedidos['pendientes'])) {
            $lines[] = 'Pedidos pendientes recientes:';
            foreach (array_slice($pedidos['pendientes'], 0, 5) as $pedido) {
                $lines[] = '- ' . $this->cell($pedido['numero_pedido'] ?? ('#' . ($pedido['id'] ?? 'N/D'))) . ' | ' . $this->cell($pedido['cliente_nombre'] ?? $pedido['envio_nombre'] ?? 'Cliente anónimo') . ' | ' . $this->money($pedido['total'] ?? 0) . ' | ' . $this->cell($pedido['metodo_pago'] ?? 'N/D');
            }
            $lines[] = '';
        }

        $lines[] = 'Instrucciones finales:';
        $lines[] = '- Prioriza lo urgente y lo que impacta ventas o inventario.';
        $lines[] = '- Termina con acciones concretas para los próximos 7 días.';
        $lines[] = '- Si un dato no está claro, dilo con honestidad.';

        return implode("\n", $lines);
    }

    private function cell($value): string {
        $text = (string) $value;
        $text = str_replace(['|', "\n", "\r"], ['\\|', ' ', ' '], $text);
        return trim($text);
    }

    private function money($value): string {
        return '$' . number_format((float) $value, 0, ',', '.');
    }

    private function int($value): string {
        return number_format((int) $value, 0, ',', '.');
    }
}
?>
