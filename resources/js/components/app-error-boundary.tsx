import { Component, type ErrorInfo, type ReactNode } from 'react';

interface Props {
    children: ReactNode;
}

interface State {
    error: Error | null;
}

export class AppErrorBoundary extends Component<Props, State> {
    state: State = { error: null };

    static getDerivedStateFromError(error: Error): State {
        return { error };
    }

    componentDidCatch(error: Error, info: ErrorInfo) {
        console.error('React render error:', error, info.componentStack);
    }

    render() {
        if (this.state.error) {
            return (
                <div className="min-h-screen bg-background p-6 text-foreground">
                    <div className="mx-auto max-w-3xl rounded-lg border border-destructive/40 bg-destructive/5 p-6">
                        <h1 className="text-lg font-semibold text-destructive">Something went wrong</h1>
                        <p className="mt-2 text-sm text-muted-foreground">
                            The page could not be displayed. Open the browser console (F12) for full details.
                        </p>
                        <pre className="mt-4 max-h-96 overflow-auto rounded-md bg-muted p-4 text-xs whitespace-pre-wrap">
                            {this.state.error.message}
                            {'\n\n'}
                            {this.state.error.stack}
                        </pre>
                        <button
                            type="button"
                            className="mt-4 rounded-md bg-primary px-4 py-2 text-sm text-primary-foreground"
                            onClick={() => window.location.reload()}
                        >
                            Reload page
                        </button>
                    </div>
                </div>
            );
        }

        return this.props.children;
    }
}
