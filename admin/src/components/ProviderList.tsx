import type { ProviderSummary } from '../types';
import { ProviderCard } from './ProviderCard';

export function ProviderList({ providers, selected, onSelect }: {
  providers: ProviderSummary[]; selected: string; onSelect: (id: string) => void;
}) {
  return <div className="techbyit-smtp-grid">{providers.map((provider) => <ProviderCard key={provider.id} provider={provider}
    selected={selected === provider.id} onSelect={() => onSelect(provider.id)} />)}</div>;
}
