import { useCallback, useEffect, useState } from 'react';
import { getProvider, getProviderSettings, getProviders } from '../api/providers';
import type { ProviderMetadata, ProviderSettings, ProviderSummary } from '../types';

export function useProviders() {
  const [providers, setProviders] = useState<ProviderSummary[]>([]);
  const [selected, setSelected] = useState('');
  const [metadata, setMetadata] = useState<ProviderMetadata | null>(null);
  const [settings, setSettings] = useState<ProviderSettings | null>(null);
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(true);

  const refresh = useCallback(async () => {
    setProviders(await getProviders());
  }, []);

  useEffect(() => {
    let active = true;
    getProviders().then((items) => { if (active) setProviders(items); })
      .catch((reason: unknown) => { if (active) setError(reason instanceof Error ? reason.message : 'Could not load providers.'); })
      .finally(() => { if (active) setLoading(false); });
    return () => { active = false; };
  }, []);

  useEffect(() => {
    if (!selected) { setMetadata(null); setSettings(null); return; }
    let active = true;
    setError('');
    Promise.all([getProvider(selected), getProviderSettings(selected)])
      .then(([provider, values]) => { if (active) { setMetadata(provider); setSettings(values); } })
      .catch((reason: unknown) => { if (active) setError(reason instanceof Error ? reason.message : 'Could not load provider settings.'); });
    return () => { active = false; };
  }, [selected]);

  return { providers, selected, setSelected, metadata, settings, setSettings, error, setError, loading, refresh };
}
