<x-layout title="Panel de Investigación Operativa">
    <x-io.queueing-panel :io="$io" />
    <x-io.decision-panel :io="$io" :cycles="$optimizableCycles" />
    <x-io.markov-panel :io="$io" />
</x-layout>
