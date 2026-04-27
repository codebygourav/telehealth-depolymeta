const LoadingSkeleton = () => {
  return (
    <div className="space-y-12 animate-pulse">
      {/* Hero Section Skeleton */}
      <section>
        <div className="max-w-3xl">
          <div className="h-16 w-3/4 bg-gray-200 rounded-lg mb-4" />
          <div className="h-6 w-2/3 bg-gray-200 rounded-lg mb-8" />
          <div className="h-16 w-full bg-gray-200 rounded-2xl" />
        </div>
      </section>

      <div className="flex flex-col lg:flex-row gap-10">
        {/* Sidebar Skeleton */}
        <aside className="w-full lg:w-72 flex-shrink-0">
          <div className="sticky top-28 space-y-8">
            <div className="h-8 w-32 bg-gray-200 rounded" />
            <div className="space-y-6">
              {[1, 2].map((i) => (
                <div key={i} className="space-y-3">
                  <div className="h-4 w-24 bg-gray-200 rounded" />
                  <div className="h-12 w-full bg-gray-200 rounded-xl" />
                </div>
              ))}
            </div>
          </div>
        </aside>

        {/* Cards Skeleton */}
        <div className="flex-grow">
          <div className="flex justify-between mb-8">
            <div className="h-6 w-48 bg-gray-200 rounded" />
            <div className="h-6 w-32 bg-gray-200 rounded" />
          </div>
          <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
            {[1, 2, 3, 4].map((i) => (
              <div key={i} className="bg-gray-100 p-6 rounded-3xl h-80" />
            ))}
          </div>
        </div>
      </div>
    </div>
  );
};

export default LoadingSkeleton;