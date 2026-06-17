import { useEffect, useRef, useState } from 'react';

import { ApiError, searchFoods } from '@/lib/api';
import type { FoodSearchResult } from '@/types/nutrition';

const DEBOUNCE_MS = 400;
const MIN_QUERY_LENGTH = 2;

type UseFoodSearchResult = {
  results: FoodSearchResult[];
  isLoading: boolean;
  error: string | null;
  hasQuery: boolean;
};

export function useFoodSearch(query: string): UseFoodSearchResult {
  const [results, setResults] = useState<FoodSearchResult[]>([]);
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const requestIdRef = useRef(0);

  const trimmedQuery = query.trim();
  const hasQuery = trimmedQuery.length >= MIN_QUERY_LENGTH;

  useEffect(() => {
    if (!hasQuery) {
      setResults([]);
      setError(null);
      setIsLoading(false);
      return;
    }

    const requestId = ++requestIdRef.current;
    setIsLoading(true);
    setError(null);

    const timer = setTimeout(async () => {
      try {
        const foods = await searchFoods(trimmedQuery);

        if (requestId !== requestIdRef.current) {
          return;
        }

        setResults(foods);
      } catch (err) {
        if (requestId !== requestIdRef.current) {
          return;
        }

        setResults([]);

        if (err instanceof ApiError) {
          setError(err.message);
        } else {
          setError('Something went wrong. Please try again.');
        }
      } finally {
        if (requestId === requestIdRef.current) {
          setIsLoading(false);
        }
      }
    }, DEBOUNCE_MS);

    return () => clearTimeout(timer);
  }, [hasQuery, trimmedQuery]);

  return { results, isLoading, error, hasQuery };
}
