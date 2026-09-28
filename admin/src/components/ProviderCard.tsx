import type { ProviderSummary } from '../types';

export function ProviderCard({ provider, selected, onSelect }: { provider: ProviderSummary; selected: boolean; onSelect: () => void }) {
  const status = provider.configured ? 'Configured' : 'Not configured';
  return <article className={`techbyit-smtp-card${selected ? ' is-selected' : ''}`}>
    <h3>{provider.name}</h3><p>{provider.description}</p>
    <p className="techbyit-smtp-meta">{provider.authentication.replace(/_/g, ' ')} · {status} · {provider.active ? 'Active' : 'Inactive'}</p>
    {provider.authentication === 'oauth2' && <p className="techbyit-smtp-meta">OAuth: {provider.authorization.status}</p>}
    {!provider.sending_supported && <p className="techbyit-smtp-meta">Sending transport coming in a later phase</p>}
    <button type="button" className="button" onClick={onSelect}>Configure</button>
  </article>;
}
